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

use IntegerNet\Solr\Config\Stub\AutosuggestConfigBuilder;
use IntegerNet\Solr\Config\ConfigContainer;
use IntegerNet\Solr\Config\Stub\FuzzyConfigBuilder;
use IntegerNet\Solr\Config\Stub\GeneralConfigBuilder;
use IntegerNet\Solr\Config\Stub\IndexingConfigBuilder;
use IntegerNet\Solr\Config\Stub\ResultConfigBuilder;
use IntegerNet\Solr\Config\Stub\ServerConfigBuilder;
use IntegerNet\Solr\Config\Stub\StoreConfigBuilder;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\SerializableConfig;
use IntegerNet\SolrSuggest\Implementor\Template;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CustomCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomCache
     */
    private $customCache;
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
        $this->customCache = new CustomCache($this->cachePoolMock);
    }

    /**
     * @test
     * @dataProvider dataWriteConfig
     * @param int $storeId
     * @param mixed[] $data
     * @param CustomHelperFactory $customHelperFactory
     */
    public function shouldStoreCustomData($storeId, array $data, CustomHelperFactory $customHelperFactory)
    {
        $dataCacheKey = "store_{$storeId}.custom";
        $helperCacheKey = "store_{$storeId}.customHelper";

        $cacheItemMocks = [];
        $cacheItemMocks[$dataCacheKey] = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMocks[$dataCacheKey]->expects($this->once())
            ->method('set')
            ->with(new Transport($data));
        $cacheItemMocks[$helperCacheKey] = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMocks[$helperCacheKey]->expects($this->once())
            ->method('set')
            ->with($customHelperFactory);

        $this->cachePoolMock->expects($this->exactly(count($cacheItemMocks)))
            ->method('getItem')
            ->withConsecutive([$dataCacheKey], [$helperCacheKey])
            ->willReturnCallback(function($key) use ($cacheItemMocks) { return $cacheItemMocks[$key];});
        $this->cachePoolMock->expects($this->exactly(count($cacheItemMocks)))
            ->method('saveDeferred')
            ->withConsecutive([$cacheItemMocks[$dataCacheKey]], $cacheItemMocks[$helperCacheKey])
            ->willReturn(true);
        $this->customCache->writeCustomCache($storeId, new Transport($data), $customHelperFactory);
    }

    /**
     * data provider
     */
    public static function dataWriteConfig()
    {
        return [
            [1, ['foo' => 'bar', 'baz' => [1, 2, 3]], new CustomHelperFactory('/path/to/Custom/Helper.php', 'Custom_Helper')]
        ];
    }
}