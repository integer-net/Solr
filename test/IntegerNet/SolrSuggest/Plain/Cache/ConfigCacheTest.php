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
     * @var \PHPUnit_Framework_MockObject_MockObject|Cache
     */
    private $cacheMock;
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        $this->cacheMock = $this->getMockForAbstractClass(Cache::class);
        $this->configCache = new ConfigCache($this->cacheMock);
    }

    /**
     * @test
     * @dataProvider dataConfig
     * @param int $storeId
     * @param SerializableConfig $config
     * @param string $templateFile
     */
    public function shouldStoreConfig($storeId, SerializableConfig $config, $templateFile)
    {
        $templateStub = $this->getMockForAbstractClass(Template::class);
        $templateStub->expects($this->any())->method('getFilename')->willReturn($templateFile);

        $this->cacheMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [$this->getConfigCacheKey($storeId), $config],
                [$this->getTemplateCacheKey($storeId), $templateFile]);
        $this->configCache->writeStoreConfig($storeId, $config, $templateStub);
    }

    /**
     * data provider
     */
    public static function dataConfig()
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

    /**
     * @test
     * @dataProvider dataConfig
     * @param int $storeId
     * @param SerializableConfig $config
     * @param string $templateFile
     */
    public function shouldReadConfig($storeId, SerializableConfig $config, $templateFile)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($this->getConfigCacheKey($storeId))
            ->willReturn($config);

        $actualConfig = $this->configCache->getConfig($storeId);
        $this->assertInstanceOf(ConfigContainer::class, $actualConfig);
        $this->assertEquals($config, $actualConfig);
    }
    /**
     * @test
     * @dataProvider dataConfig
     * @param int $storeId
     * @param SerializableConfig $config
     * @param string $templateFile
     */
    public function shouldReadTemplate($storeId, SerializableConfig $config, $templateFile)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($this->getTemplateCacheKey($storeId))
            ->willReturn($templateFile);

        $actualTemplate = $this->configCache->getTemplate($storeId);
        $this->assertInstanceOf(Template::class, $actualTemplate);
        $this->assertEquals($templateFile, $actualTemplate->getFilename());
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getConfigCacheKey($storeId)
    {
        $configCacheKey = "store_{$storeId}.config";
        return $configCacheKey;
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getTemplateCacheKey($storeId)
    {
        $templateCacheKey = "store_{$storeId}.template";
        return $templateCacheKey;
    }

}