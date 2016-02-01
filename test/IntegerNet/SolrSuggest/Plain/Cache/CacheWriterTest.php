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
use IntegerNet\SolrSuggest\Implementor\SuggestAttributeRepository;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\Template;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheWriter
     */
    private $cacheWriter;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcher
     */
    private $eventDispatcherMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttributeCache
     */
    private $attributeCacheMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CategoryCache
     */
    private $categoryCacheMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigCache
     */
    private $configCacheMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CustomCache
     */
    private $customCacheMock;
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        $this->attributeCacheMock = $this->getMockBuilder(AttributeCache::class)->disableOriginalConstructor()->getMock();
        $this->categoryCacheMock = $this->getMockBuilder(CategoryCache::class)->disableOriginalConstructor()->getMock();
        $this->eventDispatcherMock = $this->getMockForAbstractClass(EventDispatcher::class);
        $this->configCacheMock = $this->getMockBuilder(ConfigCache::class)->disableOriginalConstructor()->getMock();
        $this->customCacheMock = $this->getMockBuilder(CustomCache::class)->disableOriginalConstructor()->getMock();

        $this->cacheWriter = new CacheWriter(self::storeConfigs(), $this->eventDispatcherMock, self::templates(),
            $this->attributeCacheMock, $this->categoryCacheMock, $this->configCacheMock, $this->customCacheMock);
    }

    /**
     * @test
     */
    public function shouldWriteAllCachesForAllStores()
    {
        $storeConfigs = self::storeConfigs();
        $templates = self::templates();
        $transportObjects = [];
        $this->eventDispatcherMock->expects($this->exactly(count($storeConfigs)))
            ->method('dispatch')
            ->with('integernet_solr_autosuggest_config', $this->callback(function ($data) use (&$transportObjects) {
                    $this->assertArrayHasKey('transport', $data);
                $this->assertArrayHasKey('store_id', $data);
                $transportObjects[$data['store_id']] = $data['transport'];
                return true;
            }));
        $this->attributeCacheMock->expects($this->exactly(count($storeConfigs)))
            ->method('writeAttributeCache')
            ->withConsecutive([1], [3]);
        $this->categoryCacheMock->expects($this->exactly(1))
            ->method('writeCategoryCache')
            ->withConsecutive([1]);
        $this->configCacheMock->expects($this->exactly(count($storeConfigs)))
            ->method('writeStoreConfig')
            ->withConsecutive([1, $storeConfigs[1], $templates[1]], [3, $storeConfigs[3], $templates[3]]);
        $this->customCacheMock->expects($this->exactly(count($storeConfigs)))
            ->method('writeCustomCache')->withConsecutive(
                [1, $this->callback(function($data) use (&$transportObjects) {
                    $this->assertSame($transportObjects[1], $data);
                    return true;
                })],
                [3, $this->callback(function($data) use (&$transportObjects) {
                    $this->assertSame($transportObjects[3], $data);
                    return true;
                })]);
        $this->cacheWriter->write();
    }

    /**
     * data provider
     */
    public static function storeConfigs()
    {
        return [
            1 => new ConfigContainer(
                StoreConfigBuilder::defaultConfig()->build(),
                GeneralConfigBuilder::defaultConfig()->build(),
                ServerConfigBuilder::defaultConfig()->build(),
                IndexingConfigBuilder::defaultConfig()->build(),
                AutosuggestConfigBuilder::defaultConfig()->build(),
                FuzzyConfigBuilder::defaultConfig()->build(),
                FuzzyConfigBuilder::defaultConfig()->build(),
                ResultConfigBuilder::defaultConfig()->build()
            ),
            3 => new ConfigContainer(
                StoreConfigBuilder::defaultConfig()->build(),
                GeneralConfigBuilder::defaultConfig()->build(),
                ServerConfigBuilder::defaultConfig()->build(),
                IndexingConfigBuilder::defaultConfig()->build(),
                AutosuggestConfigBuilder::defaultConfig()->withMaxNumberCategorySuggestions(0)->build(),
                FuzzyConfigBuilder::inactiveConfig()->build(),
                FuzzyConfigBuilder::defaultConfig()->build(),
                ResultConfigBuilder::alternativeConfig()->build()
            )
        ];
    }

    /**
     * data provider
     */
    public static function templates()
    {
        return [
            1 => new \IntegerNet\SolrSuggest\Plain\Bridge\Template('var/generated/integernet_solr/store_1/autosuggest.phtml'),
            3 => new \IntegerNet\SolrSuggest\Plain\Bridge\Template('var/generated/integernet_solr/store_3/autosuggest.phtml'),
        ];
    }
}