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

use IntegerNet\Solr\Config\Stub\CategoryConfigBuilder;
use IntegerNet\Solr\Config\Stub\CmsConfigBuilder;
use IntegerNet\SolrSuggest\Plain\Config;
use IntegerNet\Solr\Config\Stub\AutosuggestConfigBuilder;
use IntegerNet\Solr\Config\Stub\FuzzyConfigBuilder;
use IntegerNet\Solr\Config\Stub\GeneralConfigBuilder;
use IntegerNet\Solr\Config\Stub\IndexingConfigBuilder;
use IntegerNet\Solr\Config\Stub\ResultConfigBuilder;
use IntegerNet\Solr\Config\Stub\ServerConfigBuilder;
use IntegerNet\Solr\Config\Stub\StoreConfigBuilder;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\SerializableConfig;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use IntegerNet\SolrSuggest\Plain\Entity\Attribute;
use IntegerNet\SolrSuggest\Plain\Bridge\Category;
use IntegerNet\SolrSuggest\Plain\Block\Template;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ActiveCategoriesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ConfigCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomDataCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomHelperCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\FilterableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\SearchableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\TemplateCacheItem;

class CacheReaderTest extends \PHPUnit_Framework_TestCase
{
    protected static $defaultStoreId = 1;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheStorage
     */
    protected $cacheMock;
    /**
     * @var CacheReader
     */
    protected $cacheReader;

    protected function setUp()
    {
        $this->cacheMock = $this->getMockForAbstractClass(CacheStorage::class);
        $this->cacheReader = new CacheReader($this->cacheMock);
    }

    /**
     * data provider
     * @return array
     */
    public static function dataConfig()
    {
        $defaultConfig = new Config(
            StoreConfigBuilder::defaultConfig()->build(),
            GeneralConfigBuilder::defaultConfig()->build(),
            ServerConfigBuilder::defaultConfig()->build(),
            IndexingConfigBuilder::defaultConfig()->build(),
            AutosuggestConfigBuilder::defaultConfig()->build(),
            FuzzyConfigBuilder::defaultConfig()->build(),
            FuzzyConfigBuilder::defaultConfig()->build(),
            ResultConfigBuilder::defaultConfig()->build(),
            CategoryConfigBuilder::defaultConfig()->build(),
            CmsConfigBuilder::defaultConfig()->build()
        );
        return [
            [self::$defaultStoreId, $defaultConfig]
        ];
    }
    /**
     * data provider
     * @return array
     */
    public static function dataTemplate()
    {
        return [
            [self::$defaultStoreId, '/path/to/magento/var/generated/integernet_solr/template.phtml']
        ];
    }

    /**
     * data provider
     * @return array
     */
    public static function dataFilterableAttributes()
    {
        $filterableAttributes = [
            Attribute::fromArray(['attribute_code' => 'color', 'label' => 'Color', 'options' => [90 => 'red', 91 => 'blue']]),
            Attribute::fromArray(['attribute_code' => 'size', 'label' => 'Size', 'options' => [92 => 'S', 93 => 'M', 94 => 'L']]),
        ];

        return [
            [self::$defaultStoreId, $filterableAttributes]
        ];
    }
    /**
     * data provider
     * @return array
     */
    public static function dataSearchableAttributes()
    {
        $searchableAttributes = [
            Attribute::fromArray(['attribute_code' => 'color', 'label' => 'Color', 'options' => [], 'solr_boost' => 1.5, 'used_for_sortby' => true]),
        ];

        return [
            [self::$defaultStoreId, $searchableAttributes]
        ];
    }

    /**
     * data provider
     * @return array
     */
    public static function dataCustomData()
    {
        $customData = new Transport(['foo' => 'bar', 'moo' => 'baa', 'sub' => ['sub' => 'way']]);
        $expectedValues = [
            'foo' => 'bar',
            'moo' => 'baa',
            'sub' => ['sub' => 'way'],
            'sub/sub' => 'way',
            '/foo' => 'bar',
        ];
        $invalidPaths = [
            'invalid',
            'foo/foo',
            'sub/invalid',
            'sub/sub/sub'
        ];

        return [
            [self::$defaultStoreId, $customData, $expectedValues, $invalidPaths]
        ];
    }

    /**
     * data provider
     * @return array
     */
    public static function dataActiveCategories()
    {
        $categories = [new Category(1, 'Books', 'books.html'), new Category(2, 'DVDs', 'dvds.html')];

        return [
            [self::$defaultStoreId, $categories]
        ];
    }


    /**
     * @test
     */
    public function shouldReadAllCaches()
    {
        $storeId = self::$defaultStoreId;
        $otherStoreId = 3;
        $this->cacheMock->expects($this->exactly(8))
            ->method('load')
            ->withConsecutive(
                // before loading: load single configuration from same store
                [new FilterableAttributesCacheItem($storeId)],
                // while loading: load rest of configuration
                [new ConfigCacheItem($storeId)],
                [new TemplateCacheItem($storeId)],
                [new SearchableAttributesCacheItem($storeId)],
                [new ActiveCategoriesCacheItem($storeId)],
                [new CustomDataCacheItem($storeId)],
                [new CustomHelperCacheItem($storeId)],
                // after loading: load configuration from other store
                [new ConfigCacheItem($otherStoreId)]
            )
            ->willReturnCallback(function(CacheItem $item) {
                $itemStub = $this->getMockForAbstractClass(CacheItem::class);
                $itemStub->expects($this->any())->method('getValue')->willReturn('dummy');
                return $itemStub;
            });
        $this->cacheReader->getFilterableAttributes($storeId);
        $this->cacheReader->load($storeId);
        $this->cacheReader->getConfig($otherStoreId);

        // consecutive calls should not trigger a cache load
        $this->cacheReader->getConfig($storeId);
        $this->cacheReader->getTemplate($storeId);
        $this->cacheReader->getSearchableAttributes($storeId);
        $this->cacheReader->getActiveCategories($storeId);
        $this->cacheReader->getCustomData($storeId);
        $this->cacheReader->getCustomHelperFactory($storeId);
    }
    /**
     * @test
     * @dataProvider dataConfig
     * @param int $storeId
     */
    public function shouldReadConfig($storeId, SerializableConfig $config)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with(new ConfigCacheItem($storeId))
            ->willReturn(new ConfigCacheItem($storeId, $config));

        $actualConfig = $this->cacheReader->getConfig($storeId);
        $this->assertInstanceOf(Config::class, $actualConfig);
        $this->assertEquals($config, $actualConfig);
    }
    /**
     * @test
     * @dataProvider dataTemplate
     * @param int $storeId
     * @param string $templateFile
     */
    public function shouldReadTemplate($storeId, $templateFile)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with(new TemplateCacheItem($storeId))
            ->willReturn(new TemplateCacheItem($storeId, new Template($templateFile)));

        $actualTemplate = $this->cacheReader->getTemplate($storeId);
        $this->assertInstanceOf(Template::class, $actualTemplate);
        $this->assertEquals($templateFile, $actualTemplate->getFilename());
    }

    /**
     * @test
     * @dataProvider dataFilterableAttributes
     */
    public function shouldReadFilterableAttributes($storeId, array $attributes)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with(new FilterableAttributesCacheItem($storeId))
            ->willReturn(new FilterableAttributesCacheItem($storeId, $attributes));

        $actualAttributes = $this->cacheReader->getFilterableAttributes($storeId);
        $this->assertEquals($attributes, $actualAttributes);
    }
    /**
     * @test
     * @dataProvider dataSearchableAttributes
     */
    public function shouldReadSearchableAttributes($storeId, array $attributes)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with(new SearchableAttributesCacheItem($storeId))
            ->willReturn(new SearchableAttributesCacheItem($storeId, $attributes));

        $actualAttributes = $this->cacheReader->getSearchableAttributes($storeId);
        $this->assertEquals($attributes, $actualAttributes);
    }

    /**
     * @test
     * @dataProvider dataActiveCategories
     * @param $storeId
     * @param array $categories
     */
    public function shouldReadActiveCategories($storeId, array $categories)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with(new ActiveCategoriesCacheItem($storeId))
            ->willReturn(new ActiveCategoriesCacheItem($storeId, $categories));

        $actualCategories = $this->cacheReader->getActiveCategories($storeId);
        $this->assertEquals($categories, $actualCategories);
    }


    /**
     * @test
     * @dataProvider dataCustomData
     * @param $storeId
     * @param Transport $customData
     * @param array $expectedValues
     * @param array $invalidPaths
     */
    public function shouldReadCustomData($storeId, Transport $customData, array $expectedValues, array $invalidPaths)
    {
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with(new CustomDataCacheItem($storeId))
            ->willReturn(new CustomDataCacheItem($storeId, $customData));

        $actualCustomData = $this->cacheReader->getCustomData($storeId);
        foreach ($expectedValues as $inputPath => $expectedValue) {
            $this->assertEquals($expectedValue, $this->cacheReader->getCustomData($storeId, $inputPath));
        }
        foreach ($invalidPaths as $inputPath) {
            $exceptionThrown = false;
            try {
                $this->cacheReader->getCustomData($storeId, $inputPath);
            } catch (CacheItemNotFoundException $e) {
                $exceptionThrown = true;
            }
            $this->assertTrue($exceptionThrown, 'Exception should be thrown with invalid input path');
        }
        $this->assertEquals($customData, $actualCustomData);
    }

    /**
     * @test
     */
    public function shouldReadCustomHelper()
    {
        $customHelperFactory = $this->getMockBuilder(CustomHelperFactory::class)->disableOriginalConstructor()->getMock();
        $storeId = self::$defaultStoreId;
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with(new CustomHelperCacheItem($storeId))
            ->willReturn(new CustomHelperCacheItem($storeId, $customHelperFactory));

        $actualCustomHelperFactory = $this->cacheReader->getCustomHelperFactory($storeId);
        $this->assertSame($customHelperFactory, $actualCustomHelperFactory);
    }
}