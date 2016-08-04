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

use IntegerNet\SolrSuggest\Plain\Config;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\SerializableConfig;
use IntegerNet\SolrSuggest\Plain\Entity\SerializableAttributeRepository;
use IntegerNet\SolrSuggest\Implementor\SerializableSuggestCategory;
use IntegerNet\SolrSuggest\Implementor\SerializableCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\TemplateRepository;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ActiveCategoriesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ConfigCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomDataCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomHelperCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\FilterableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\SearchableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\TemplateCacheItem;

/**
 * Takes data from application and stores it in own cache
 */
class CacheWriter
{
    const EVENT_CUSTOM_CONFIG = 'integernet_solr_autosuggest_config';
    /**
     * @var CacheStorage
     */
    protected $cache;
    /**
     * @var SerializableAttributeRepository
     */
    private $attributeRepository;
    /**
     * @var SerializableCategoryRepository
     */
    private $categoryRepository;
    /**
     * @var CustomHelperFactory
     */
    private $customHelperFactory;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    /**
     * @todo take normal repositories and instantiate converters here (or better, in a CacheWriterFactory)
     * @param CacheStorage $cache
     * @param SerializableAttributeRepository $attributeRepository
     * @param SerializableCategoryRepository $categoryRepository
     * @param CustomHelperFactory $customHelperFactory
     * @param EventDispatcher $eventDispatcher
     * @param TemplateRepository $templates
     */
    public function __construct(CacheStorage $cache, SerializableAttributeRepository $attributeRepository,
                                SerializableCategoryRepository $categoryRepository, CustomHelperFactory $customHelperFactory,
                                EventDispatcher $eventDispatcher, TemplateRepository $templates)
    {
        $this->cache = $cache;
        $this->attributeRepository = $attributeRepository;
        $this->categoryRepository = $categoryRepository;
        $this->customHelperFactory = $customHelperFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->templateRepository = $templates;
    }
    /**
     * Write everything to cache
     *
     * @param \IntegerNet\Solr\Implementor\Config[] $storeConfigs
     */
    public function write(array $storeConfigs)
    {
        foreach ($storeConfigs as $storeId => $config) {
            $this->writeStoreConfig($storeId, Config::fromConfig($config));
            $this->writeAttributeCache($storeId);
            $this->writeCustomCache($storeId);
            $doNotCacheCategories = $config->getAutosuggestConfig()->getMaxNumberCategorySuggestions() == 0;
            $this->writeCategoryCache($storeId, $doNotCacheCategories);
        }
    }

    /**
     * @param $storeId
     */
    private function writeAttributeCache($storeId)
    {
        $attributes = $this->attributeRepository->findFilterableInSearchAttributes($storeId);
        $this->cache->save(new FilterableAttributesCacheItem($storeId, $attributes));

        $searchableAttributes = $this->attributeRepository->findSearchableAttributes($storeId);
        $this->cache->save(new SearchableAttributesCacheItem($storeId, $searchableAttributes));
    }

    /**
     * @param $storeId
     */
    private function writeCategoryCache($storeId, $empty)
    {
        $categories = array();
        if (! $empty) {
            $categories = $this->categoryRepository->findActiveCategories($storeId);
        }
        $this->cache->save(new ActiveCategoriesCacheItem($storeId, $categories));
    }

    /**
     * @param int                $storeId       The store id
     * @param SerializableConfig $config     The store configuration for $storeId
     */
    private function writeStoreConfig($storeId, SerializableConfig $config)
    {
        $this->cache->save(new ConfigCacheItem($storeId, $config));
        $this->cache->save(new TemplateCacheItem($storeId, $this->templateRepository->getTemplateByStoreId($storeId)));
    }
    
    /**
     * @param int $storeId  The store id
     */
    private function writeCustomCache($storeId)
    {
        $transport = new Transport();
        $this->eventDispatcher->dispatch(self::EVENT_CUSTOM_CONFIG,
            array('store_id' => $storeId, 'transport' => $transport));
        $this->cache->save(new CustomDataCacheItem($storeId, $transport));
        $this->cache->save(new CustomHelperCacheItem($storeId, $this->customHelperFactory));
    }

}