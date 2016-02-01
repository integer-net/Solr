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
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\SerializableConfig;
use IntegerNet\SolrSuggest\Implementor\Template;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class ConfigCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigCache
     */
    private $configCache;
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
        $this->configCache = new ConfigCache($this->cachePoolMock);
    }

    /**
     * @test
     * @dataProvider dataWriteConfig
     * @param int $storeId
     * @param SerializableConfig $config
     * @param string $templateFile
     */
    public function shouldStoreConfig($storeId, SerializableConfig $config, $templateFile)
    {
        $configCacheKey = "store_{$storeId}.config";
        $templateCacheKey = "store_{$storeId}.template";

        $templateStub = $this->getMockForAbstractClass(Template::class);
        $templateStub->expects($this->any())->method('getFilename')->willReturn($templateFile);

        $cacheItemMocks = array();
        $cacheItemMocks[$configCacheKey] = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMocks[$configCacheKey]->expects($this->once())
            ->method('set')
            ->with($config);
        $cacheItemMocks[$templateCacheKey] = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMocks[$templateCacheKey]->expects($this->once())
            ->method('set')
            ->with($templateFile);

        $this->cachePoolMock->expects($this->exactly(count($cacheItemMocks)))
            ->method('getItem')
            ->withConsecutive([$configCacheKey], [$templateCacheKey])
            ->willReturnCallback(function($key) use ($cacheItemMocks) { return $cacheItemMocks[$key];});
        $this->cachePoolMock->expects($this->exactly(count($cacheItemMocks)))
            ->method('saveDeferred')
            ->withConsecutive([$cacheItemMocks[$configCacheKey]], [$cacheItemMocks[$templateCacheKey]])
            ->willReturn(true);
        $this->configCache->writeStoreConfig($storeId, $config, $templateStub);
    }

    /**
     * data provider
     */
    public static function dataWriteConfig()
    {
        $defaultStoreId = 1;
        $defaultConfig = new ConfigContainer(
            StoreConfigBuilder::defaultConfig()->build(),
            GeneralConfigBuilder::defaultConfig()->build(),
            ServerConfigBuilder::defaultConfig()->build(),
            IndexingConfigBuilder::defaultConfig()->build(),
            AutosuggestConfigBuilder::defaultConfig()->build(),
            FuzzyConfigBuilder::defaultConfig()->build(),
            FuzzyConfigBuilder::defaultConfig()->build(),
            ResultConfigBuilder::defaultConfig()->build()
        );
        return [
            [$defaultStoreId, $defaultConfig, '/path/to/magento/var/generated/integernet_solr/template.phtml']
        ];
    }
}