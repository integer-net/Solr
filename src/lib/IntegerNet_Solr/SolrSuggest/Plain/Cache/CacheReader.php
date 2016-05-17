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

use IntegerNet\Solr\Implementor\Config;
use IntegerNet\SolrSuggest\Plain\Entity\SerializableAttribute;
use IntegerNet\SolrSuggest\Implementor\SerializableSuggestCategory;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use IntegerNet\SolrSuggest\Plain\Block\Template;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ActiveCategoriesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\ConfigCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomDataCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\CustomHelperCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\FilterableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\SearchableAttributesCacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\Item\TemplateCacheItem;

class CacheReader
{
    private $loadedSearchableAttributes;
    private $loadedActiveCategories;
    private $loadedTemplate;
    private $loadedCustomHelperFactory;
    /**
     * @var CacheStorage
     */
    private $cache;

    private $loadedConfig = array();
    private $loadedFilterableAttributes = array();
    private $loadedCustomData = array();

    /**
     * @param CacheStorage $cache
     */
    public function __construct(CacheStorage $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Load all required items for given store from cache
     *
     * @throws CacheItemNotFoundException if any cache item is not found
     */
    public function load($storeId)
    {
        $this->getConfig($storeId);
        $this->getTemplate($storeId);
        $this->getFilterableAttributes($storeId);
        $this->getSearchableAttributes($storeId);
        $this->getActiveCategories($storeId);
        $this->getCustomData($storeId);
        $this->getCustomHelperFactory($storeId);
    }

    /**
     * @param $storeId
     * @return SerializableAttribute[]
     * @throws CacheItemNotFoundException
     */
    public function getFilterableAttributes($storeId)
    {
        if (! isset($this->loadedFilterableAttributes[$storeId])) {
            $this->loadedFilterableAttributes[$storeId] = $this->cache->load(FilterableAttributesCacheItem::createEmpty($storeId))->getValue();
        }
        return $this->loadedFilterableAttributes[$storeId];
    }

    /**
     * @param $storeId
     * @return SerializableAttribute[]
     * @throws CacheItemNotFoundException
     */
    public function getSearchableAttributes($storeId)
    {
        if (! isset($this->loadedSearchableAttributes[$storeId])) {
            $this->loadedSearchableAttributes[$storeId] = $this->cache->load(SearchableAttributesCacheItem::createEmpty($storeId))->getValue();
        }
        return $this->loadedSearchableAttributes[$storeId];
    }
    /**
     * @param $storeId
     * @return SerializableSuggestCategory[]
     * @throws CacheItemNotFoundException
     */
    public function getActiveCategories($storeId)
    {
        if (! isset($this->loadedActiveCategories[$storeId])) {
            $this->loadedActiveCategories[$storeId] = $this->cache->load(ActiveCategoriesCacheItem::createEmpty($storeId))->getValue();
        }
        return $this->loadedActiveCategories[$storeId];
    }

    /**
     * @param $storeId
     * @return Config
     * @throws CacheItemNotFoundException
     */
    public function getConfig($storeId)
    {
        if (! isset($this->loadedConfig[$storeId])) {
            $this->loadedConfig[$storeId] = $this->cache->load(ConfigCacheItem::createEmpty($storeId))->getValue();
        }
        return $this->loadedConfig[$storeId];
    }

    /**
     * @param $storeId
     * @return Template
     * @throws CacheItemNotFoundException
     */
    public function getTemplate($storeId)
    {
        if (! isset($this->loadedTemplate[$storeId])) {
            $this->loadedTemplate[$storeId] = $this->cache->load(TemplateCacheItem::createEmpty($storeId))->getValue();
        }
        return $this->loadedTemplate[$storeId];
    }

    /**
     * @param string $path
     * @return mixed
     * @throws CacheItemNotFoundException
     */
    public function getCustomData($storeId, $path = null)
    {
        if (! isset($this->loadedCustomData[$storeId])) {
            $this->loadedCustomData[$storeId] = $this->cache->load(CustomDataCacheItem::createEmpty($storeId))->getValue();
        }
        $result = $this->loadedCustomData[$storeId];
        if (!is_null($path) && isset($result[$path])) {
            return $result[$path];
        }
        foreach (array_filter(explode('/', $path)) as $pathElement) {
            if ((is_array($result) || $result instanceof \ArrayAccess) && isset ($result[$pathElement])) {
                $result = $result[$pathElement];
            } else {
                throw new CacheItemNotFoundException("Custom data {$path} not found in cache");
            }
        }
        return $result;
    }

    /**
     * @param $storeId
     * @return CustomHelperFactory
     * @throws CacheItemNotFoundException
     */
    public function getCustomHelperFactory($storeId)
    {
        if (! isset($this->loadedCustomHelperFactory[$storeId])) {
            $this->loadedCustomHelperFactory[$storeId] = $this->cache->load(CustomHelperCacheItem::createEmpty($storeId))->getValue();
        }
        return $this->loadedCustomHelperFactory[$storeId];
    }
}