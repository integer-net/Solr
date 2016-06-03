<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
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
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrSuggest\Plain\Entity\SerializableAttributeRepository;
use IntegerNet\SolrSuggest\Implementor\SerializableCategoryRepository;
use IntegerNet\SolrSuggest\Plain\Block\Template;
use IntegerNet\SolrSuggest\Implementor\TemplateRepository;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use IntegerNet\SolrSuggest\Plain\Entity\Attribute;
use IntegerNet\SolrSuggest\Plain\Bridge\Category;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ActiveCategoriesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ConfigCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomDataCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomHelperCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\FilterableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\SearchableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\TemplateCacheItem;


class CacheWriterTest extends \PHPUnit_Framework_TestCase
{
    protected static $defaultStoreId = 1;
    /**
     * @var CacheWriter
     */
    protected $cacheWriter;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SerializableAttributeRepository
     */
    protected $attributeRepositoryStub;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheStorage
     */
    protected $cacheMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SerializableCategoryRepository
     */
    protected $categoryRepositoryStub;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TemplateRepository
     */
    protected $templateRepositoryStub;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CustomHelperFactory
     */
    protected $customHelperFactoryStub;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcher
     */
    protected $eventDispatcherMock;

    protected function setUp()
    {
        $this->cacheMock = $this->getMockForAbstractClass(CacheStorage::class);
        $this->attributeRepositoryStub = $this->getMockForAbstractClass(SerializableAttributeRepository::class);
        $this->categoryRepositoryStub = $this->getMockForAbstractClass(SerializableCategoryRepository::class);
        $this->templateRepositoryStub = $this->getMockForAbstractClass(TemplateRepository::class);
        $this->customHelperFactoryStub = new CustomHelperFactory('/path/to/Custom/Helper.php', 'Custom_Helper'); // global values
        $this->eventDispatcherMock = $this->getMockForAbstractClass(EventDispatcher::class);

        $this->cacheWriter = new CacheWriter($this->cacheMock,
            $this->attributeRepositoryStub,
            $this->categoryRepositoryStub,
            $this->customHelperFactoryStub,
            $this->eventDispatcherMock,
            $this->templateRepositoryStub);
    }

    /**
     * @dataProvider dataStoreConfigs
     * @param CacheWriterTestParameters[] $storeParameters
     * @test
     */
    public function shouldWriteEverythingToCache(array $storeParameters)
    {
        $inputStoreConfigs = [];
        $expectedCacheSaveCalls = [];
        $index = 0;
        foreach ($storeParameters as $parameters) {
            $storeId = $parameters->getStoreId();
            $inputStoreConfigs[$storeId] = $parameters->getConfig();

            $templateStub = $this->prepareStubsByParametersAndReturnTemplateStub($index, $parameters);
            $transportObject =& $this->mockEventDispatcherAndReturnTransportReference(
                $parameters->getCustomData(), $index
            );

            $expectedCacheSaveCalls = array_merge($expectedCacheSaveCalls, [
                new ConfigCacheItem($parameters->getStoreId(), $parameters->getConfig()),
                new TemplateCacheItem($parameters->getStoreId(), $templateStub),
                new FilterableAttributesCacheItem($parameters->getStoreId(), $parameters->getFilterableAttributes()),
                new SearchableAttributesCacheItem($parameters->getStoreId(), $parameters->getSearchableAttributes()),
                $this->callback(function (CustomDataCacheItem $item) use (&$transportObject, $storeId) {
                    $this->assertContains((string)$storeId, $item->getKey(), 'Cache key should contain store id');
                    $data = $item->getValue();
                    $this->assertSame($transportObject, $data);
                    return true;
                }),
                new CustomHelperCacheItem($parameters->getStoreId(), $this->customHelperFactoryStub),
                new ActiveCategoriesCacheItem($parameters->getStoreId(), $parameters->getActiveCategories())
            ]);
            ++$index;
        }

        $this->cacheMock->expects($this->exactly(count($expectedCacheSaveCalls)))->method('save');
        foreach ($expectedCacheSaveCalls as $index => $expectedCacheSaveCall) {
            $this->cacheMock->expects($this->at($index))
                ->method('save')
                ->with($this->callback(function(CacheItem $item) use ($expectedCacheSaveCall) {
                    if ($expectedCacheSaveCall instanceof \PHPUnit_Framework_Constraint) {
                        $expectedCacheSaveCall->evaluate($item);
                        return true;
                    }
                    $this->assertEquals($expectedCacheSaveCall, $item);
                    return true;
                }));
        }

        $this->cacheWriter->write($inputStoreConfigs);
    }


    /**
     * @param array $dataToAdd
     * @param int $callIndex
     * @return Transport
     */
    protected function &mockEventDispatcherAndReturnTransportReference(array $dataToAdd, $callIndex)
    {
        $transportObject = null;
        $this->eventDispatcherMock->expects($this->at($callIndex))
            ->method('dispatch')
        ->with('integernet_solr_autosuggest_config', $this->callback(function ($data) use (&$transportObject, $dataToAdd) {
            $this->assertArrayHasKey('transport', $data);
                $this->assertArrayHasKey('store_id', $data);
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
     * data provider
     *
     * @return CacheWriterTestParameters[][]
     */
    public static function dataStoreConfigs()
    {
        $filterableAttributes = [
            Attribute::fromArray(['attribute_code' => 'color', 'label' => 'Color', 'options' => [90 => 'red', 91 => 'blue'], 'images' => [90 => 'red.jpg', 91 => 'blue.jpg']]),
            Attribute::fromArray(['attribute_code' => 'size', 'label' => 'Size', 'options' => [92 => 'S', 93 => 'M', 94 => 'L']]),
        ];
        $searchableAttributes = [
            Attribute::fromArray(['attribute_code' => 'color', 'label' => 'Color', 'options' => [], 'solr_boost' => 1.5, 'used_for_sortby' => true]),
        ];
        $categories = [new Category(1, 'Books', 'books.html'), new Category(2, 'DVDs', 'dvds.html')];


        $parameters = [];
        $parameters[] = new CacheWriterTestParameters(1,
            new Config(
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
            ),
            '/path/to/magento/var/generated/integernet_solr/template.phtml',
            $filterableAttributes,
            $searchableAttributes,
            $categories,
            ['foo' => 'bar', 'baz' => [1, 2, 3]],
            '/path/to/Custom/Helper.php',
            'Custom_Helper'
        );
        $parameters[] = new CacheWriterTestParameters(3,
            new Config(
                StoreConfigBuilder::defaultConfig()->build(),
                GeneralConfigBuilder::defaultConfig()->build(),
                ServerConfigBuilder::defaultConfig()->build(),
                IndexingConfigBuilder::defaultConfig()->build(),
                AutosuggestConfigBuilder::defaultConfig()->withMaxNumberCategorySuggestions(0)->build(),
                FuzzyConfigBuilder::inactiveConfig()->build(),
                FuzzyConfigBuilder::defaultConfig()->build(),
                ResultConfigBuilder::alternativeConfig()->build(),
                CategoryConfigBuilder::defaultConfig()->build(),
                CmsConfigBuilder::defaultConfig()->build()
            ),
            '/path/to/magento/var/generated/integernet_solr/store_3/template.phtml',
            $filterableAttributes,
            $searchableAttributes,
            [], // no categories if category suggestions feature is disabled
            ['foo' => 'bistro', 'baz' => [3, 2, 1]],
            '/path/to/Custom/Helper.php',
            'Custom_Helper'
        );

        return [[$parameters]];
    }

    /**
     * @param $index
     * @param CacheWriterTestParameters $parameters
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareStubsByParametersAndReturnTemplateStub($index, CacheWriterTestParameters $parameters)
    {
        $storeId = $parameters->getStoreId();
        $templateStub = new Template($parameters->getTemplateFile());
        $this->templateRepositoryStub->expects($this->at($index))
            ->method('getTemplateByStoreId')
            ->with($storeId)
            ->willReturn($templateStub);

        $this->attributeRepositoryStub->expects($this->at($index * 2))
            ->method('findFilterableInSearchAttributes')
            ->with($storeId)
            ->willReturn($parameters->getFilterableAttributes());
        $this->attributeRepositoryStub->expects($this->at($index * 2 + 1))
            ->method('findSearchableAttributes')
            ->with($storeId)
            ->willReturn($parameters->getSearchableAttributes());

        if (count($parameters->getActiveCategories())) {
            $this->categoryRepositoryStub->expects($this->at($index))
                ->method('findActiveCategories')
                ->with($storeId)
                ->willReturn($parameters->getActiveCategories());
            return $templateStub;
        }
        return $templateStub;
    }
}
class CacheWriterTestParameters
{
    /** @var  int */
    private $storeId;
    /** @var  Config */
    private $config;
    /** @var  string */
    private $templateFile;
    /** @var  Attribute[] */
    private $filterableAttributes;
    /** @var  Attribute[] */
    private $searchableAttributes;
    /** @var  Category[] */
    private $activeCategories;
    /** @var  mixed[] */
    private $customData;

    /**
     * @param int $storeId
     * @param Config $config
     * @param string $templateFile
     * @param \IntegerNet\SolrSuggest\Plain\Entity\Attribute[] $filterableAttributes
     * @param \IntegerNet\SolrSuggest\Plain\Entity\Attribute[] $searchableAttributes
     * @param \IntegerNet\SolrSuggest\Plain\Bridge\Category[] $activeCategories
     * @param array $customData
     */
    public function __construct($storeId, Config $config, $templateFile, array $filterableAttributes,
                                array $searchableAttributes, array $activeCategories, array $customData)
    {
        $this->storeId = $storeId;
        $this->config = $config;
        $this->templateFile = $templateFile;
        $this->filterableAttributes = $filterableAttributes;
        $this->searchableAttributes = $searchableAttributes;
        $this->activeCategories = $activeCategories;
        $this->customData = $customData;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }

    /**
     * @return \IntegerNet\SolrSuggest\Plain\Entity\Attribute[]
     */
    public function getFilterableAttributes()
    {
        return $this->filterableAttributes;
    }

    /**
     * @return \IntegerNet\SolrSuggest\Plain\Entity\Attribute[]
     */
    public function getSearchableAttributes()
    {
        return $this->searchableAttributes;
    }

    /**
     * @return Category[]
     */
    public function getActiveCategories()
    {
        return $this->activeCategories;
    }

    /**
     * @return \mixed[]
     */
    public function getCustomData()
    {
        return $this->customData;
    }

}