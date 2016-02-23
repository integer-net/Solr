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
use IntegerNet\Solr\Config\Stub\AutosuggestConfigBuilder;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Stub\AttributeStub;
use IntegerNet\Solr\Implementor\Stub\SourceStub;
use IntegerNet\SolrSuggest\Plain\Entity\SerializableAttribute;
use IntegerNet\SolrSuggest\Plain\Entity\Attribute;
use IntegerNet\SolrSuggest\Plain\Entity\Source;

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
     * @var AttributesToSerializableAttributes
     */
    private $serializableAttributeRepository;

    protected function setUp()
    {
        $autosuggestConfig = AutosuggestConfigBuilder::defaultConfig()->withAttributeFilterSuggestions([
            ['attribute_code' => 'attribute1', 'max_number_suggestions' => 0, 'sorting' => 0],
            ['attribute_code' => 'suggest_attribute_2', 'max_number_suggestions' => 20, 'sorting' => 2],
            ['attribute_code' => 'suggest_attribute_1', 'max_number_suggestions' => 10, 'sorting' => 1],
        ])->build();
        $this->attributeRepositoryStub = $this->getMockForAbstractClass(AttributeRepository::class);
        $this->eventDispatcherMock = $this->getMockForAbstractClass(EventDispatcher::class);
        $this->serializableAttributeRepository = new AttributesToSerializableAttributes(
            $this->attributeRepositoryStub, $this->eventDispatcherMock, [self::DEFAULT_STORE_ID => $autosuggestConfig]);
    }
    /**
     * @test
     * @dataProvider dataFilterableInSearch
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
     * @dataProvider dataSearchable
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

    public static function dataSearchable()
    {
        return self::dataAttributeRepository(false);
    }

    public static function dataFilterableInSearch()
    {
        return self::dataAttributeRepository(true);
    }

    private static function dataAttributeRepository($onlyFilterable)
    {
        $params = [
            'single_attribute_with_custom_data' => [
                self::DEFAULT_STORE_ID,
                [AttributeStub::sortableString('attribute1')],
                [new Attribute('attribute1', 'attribute1', 0, new Source([]), true, ['custom_key' => 'custom_value'])],
                [['custom_key' => 'custom_value']]
            ],
            'suggest_attributes' => [
                self::DEFAULT_STORE_ID,
                [
                    AttributeStub::filterable('suggest_attribute_1', [11 => 'value-1-1', 12 => 'value-1-2']),
                    AttributeStub::filterable('suggest_attribute_2', [21 => 'value-2-1', 22 => 'value-2-2']),
                    AttributeStub::filterable('other_attribute', [31 => 'value-3-1', 32 => 'value-3-2']),
                ],
                [
                    new Attribute('suggest_attribute_1', 'suggest_attribute_1', 0, new Source([11 => 'value-1-1', 12 => 'value-1-2']), false, []),
                    new Attribute('suggest_attribute_2', 'suggest_attribute_2', 0, new Source([21 => 'value-2-1', 22 => 'value-2-2']), false, []),
                ],
                [[]]
            ]
        ];
        if (! $onlyFilterable) {
            $params['suggest_attributes'][2][] = new Attribute('other_attribute', 'other_attribute', 0, new Source([31 => 'value-3-1', 32 => 'value-3-2']), false, []);
        }
        return $params;
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