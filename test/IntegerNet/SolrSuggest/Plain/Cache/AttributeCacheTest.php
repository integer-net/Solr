<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache;

use IntegerNet\SolrSuggest\Implementor\SuggestAttributeRepository;
use IntegerNet\SolrSuggest\Plain\Bridge\Attribute;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class AttributeCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeCache
     */
    private $attributeCache;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SuggestAttributeRepository
     */
    private $attributeRepositoryStub;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheItemPoolInterface
     */
    private $cachePoolMock;
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        $this->cachePoolMock = $this->getMockForAbstractClass(CacheItemPoolInterface::class);
        $this->attributeRepositoryStub = $this->getMockForAbstractClass(SuggestAttributeRepository::class);
        $this->attributeCache = new AttributeCache($this->cachePoolMock, $this->attributeRepositoryStub);
    }
    /**
     * @test
     * @dataProvider dataStoreIds
     * @param int $storeId
     */
    public function shouldStoreAttributes($storeId)
    {
        $attributesCacheKey = "store_{$storeId}.attributes";
        $searchableAttributesCacheKey = "store_{$storeId}.searchable_attributes";
        $dataAttributeArray = [
            new Attribute(['code' => 'color', 'label' => 'Color', 'options' => [90 => 'red', 91 => 'blue']]),
            new Attribute(['code' => 'size', 'label' => 'Size', 'options' => [92 => 'S', 93 => 'M', 94 => 'L']]),
        ];
        $dataSearchableAttributeArray = [
            new Attribute(['code' => 'color', 'label' => 'Color', 'solr_boost' => 1.5, 'used_for_sortby' => true]),
        ];

        $this->attributeRepositoryStub->expects($this->any())
            ->method('findFilterableInSearchAttributes')
            ->with($storeId)
            ->willReturn($dataAttributeArray);
        $this->attributeRepositoryStub->expects($this->any())
            ->method('findSearchableAttributes')
            ->with($storeId)
            ->willReturn($dataSearchableAttributeArray);

        $cacheItemMocks = array();
        $cacheItemMocks[$attributesCacheKey] = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMocks[$attributesCacheKey]->expects($this->once())
            ->method('set')
            ->with($dataAttributeArray);
        $cacheItemMocks[$searchableAttributesCacheKey] = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMocks[$searchableAttributesCacheKey]->expects($this->once())
            ->method('set')
            ->with($dataSearchableAttributeArray);

        $this->cachePoolMock->expects($this->exactly(count($cacheItemMocks)))
            ->method('getItem')
            ->withConsecutive([$attributesCacheKey], [$searchableAttributesCacheKey])
            ->willReturnCallback(function($key) use ($cacheItemMocks) { return $cacheItemMocks[$key];});
        $this->cachePoolMock->expects($this->exactly(count($cacheItemMocks)))
            ->method('saveDeferred')
            ->withConsecutive([$cacheItemMocks[$attributesCacheKey]], [$cacheItemMocks[$searchableAttributesCacheKey]])
            ->willReturn(true);

        $this->attributeCache->writeAttributeCache($storeId);
    }

    public static function dataStoreIds()
    {
        return [
            [1],
            [2],
        ];
    }
}