<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache\Convert;

use Composer\EventDispatcher\Event;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Stub\AttributeStub;
use IntegerNet\Solr\Implementor\Stub\SourceStub;
use IntegerNet\SolrSuggest\Implementor\SerializableAttribute;
use IntegerNet\SolrSuggest\Plain\Bridge\Attribute;
use IntegerNet\SolrSuggest\Plain\Bridge\Source;

class SerializableAttributeRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcher
     */
    private $eventDispatcherMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttributeRepository
     */
    private $attributeRepositoryStub;
    /**
     * @var SerializableAttributeRepository
     */
    private $serializableAttributeRepository;

    protected function setUp()
    {
        $this->attributeRepositoryStub = $this->getMockForAbstractClass(AttributeRepository::class);
        $this->eventDispatcherMock = $this->getMockForAbstractClass(EventDispatcher::class);
        $this->serializableAttributeRepository = new SerializableAttributeRepository(
            $this->attributeRepositoryStub, $this->eventDispatcherMock);
    }
    /**
     * @test
     * @dataProvider dataAttributeRepository
     * @param int $storeId
     * @param \IntegerNet\Solr\Implementor\Attribute[] $attributes
     * @param SerializableAttribute[] $expectedResult
     * @param mixed[] $customData
     */
    public function shouldReturnSerializedAttributesForFilter($storeId, $attributes, $expectedResult, $customData)
    {
        $this->setUpEventDispatcher($storeId, $customData);
        $this->attributeRepositoryStub->expects($this->once())
            ->method('getFilterableInSearchAttributes')
            ->with($storeId)
            ->willReturn($attributes);
        $actualResult = $this->serializableAttributeRepository->findFilterableInSearchAttributes($storeId);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @dataProvider dataAttributeRepository
     * @param int $storeId
     * @param \IntegerNet\Solr\Implementor\Attribute[] $attributes
     * @param SerializableAttribute[] $expectedResult
     * @param mixed[] $customData
     */
    public function shouldReturnSerializedAttributesForSearch($storeId, $attributes, $expectedResult, $customData)
    {
        $this->setUpEventDispatcher($storeId, $customData);
        $this->attributeRepositoryStub->expects($this->once())
            ->method('getSearchableAttributes')
            ->with($storeId)
            ->willReturn($attributes);
        $actualResult = $this->serializableAttributeRepository->findSearchableAttributes($storeId);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public static function dataAttributeRepository()
    {
        return [
            'single_attribute' => [
                self::DEFAULT_STORE_ID,
                [AttributeStub::sortableString('attribute1')],
                [new Attribute('attribute1', 'attribute1', 0, new SourceStub(), true, ['custom_key' => 'custom_value'])],
                [['custom_key' => 'custom_value']]
            ]
        ];
    }

    /**
     * @param array $dataToAdd
     * @param int $callIndex
     * @return Transport
     */
    protected function &mockEventDispatcherAndReturnTransportReference(array $dataToAdd, $callIndex, $storeId)
    {
        $transportObject = null;
        $this->eventDispatcherMock->expects($this->at($callIndex))
            ->method('dispatch')
            ->with('integernet_solr_autosuggest_config_attribute', $this->callback(function ($data) use (&$transportObject, $dataToAdd, $storeId) {
                $this->assertArrayHasKey('transport', $data);
                $this->assertArrayHasKey('attribute', $data);
                $this->assertArrayHasKey('store_id', $data);
                $this->assertInstanceOf(\IntegerNet\Solr\Implementor\Attribute::class, $data['attribute']);
                $this->assertEquals($storeId, $data['store_id']);
                $this->assertInstanceOf(Transport::class, $data['transport']);
                foreach ($dataToAdd as $key => $value) {
                    $data['transport']->setData($key, $value);
                }
                $transportObject = $data['transport'];
                return true;
            }));
        return $transportObject;
    }

    /**
     * @param $storeId
     * @param $customData
     */
    private function setUpEventDispatcher($storeId, $customData)
    {
        $index = 0;
        foreach ($customData as $customAttributeData) {
            $this->mockEventDispatcherAndReturnTransportReference(
                $customAttributeData, $index, $storeId
            );
            $index++;
        }
    }


}