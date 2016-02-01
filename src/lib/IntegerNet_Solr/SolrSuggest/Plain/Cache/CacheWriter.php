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

use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrSuggest\Implementor\SuggestAttributeRepository;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\Template;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Takes data from application and stores it in own cache
 *
 * @package IntegerNet\SolrSuggest\Plain\Cache
 */
class CacheWriter
{
    const EVENT_CUSTOM_CONFIG = 'integernet_solr_autosuggest_config';
    /**
     * @var Config[]
     */
    private $storeConfigs;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var Template[]
     */
    private $templates;
    /**
     * @var AttributeCache
     */
    private $attributeCache;
    /**
     * @var CategoryCache
     */
    private $categoryCache;
    /**
     * @var ConfigCache
     */
    private $configCache;
    /**
     * @var CustomCache
     */
    private $customCache;

    /**
     * CacheWriter constructor.
     * @param \IntegerNet\Solr\Implementor\Config[] $storeConfigs
     * @param EventDispatcher $eventDispatcher
     * @param Template[] $templates
     * @param AttributeCache $attributeCache
     * @param CategoryCache $categoryCache
     * @param ConfigCache $configCache
     * @param CustomCache $customCache
     */
    public function __construct(array $storeConfigs, EventDispatcher $eventDispatcher, array $templates,
                                AttributeCache $attributeCache, CategoryCache $categoryCache, ConfigCache $configCache,
                                CustomCache $customCache)
    {
        $this->storeConfigs = $storeConfigs;
        $this->eventDispatcher = $eventDispatcher;
        $this->templates = $templates;
        $this->attributeCache = $attributeCache;
        $this->categoryCache = $categoryCache;
        $this->configCache = $configCache;
        $this->customCache = $customCache;
    }


    public function write()
    {
        foreach ($this->storeConfigs as $storeId => $config) {
            $transport = new Transport();
            $this->eventDispatcher->dispatch(self::EVENT_CUSTOM_CONFIG,
                array('store_id' => $storeId, 'transport' => $transport));
            $this->configCache->writeStoreConfig($storeId, $config, $this->templates[$storeId]);
            $this->attributeCache->writeAttributeCache($storeId);
            $this->categoryCache->writeCategoryCache($storeId);
            $this->customCache->writeCustomCache($storeId, $transport);
        }
    }
}