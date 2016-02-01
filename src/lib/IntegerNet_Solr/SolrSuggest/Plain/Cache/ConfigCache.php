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
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrSuggest\Implementor\Template;
use Psr\Cache\CacheItemPoolInterface;

class ConfigCache
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool;

    /**
     * ConfigCache constructor.
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * @param $storeId           The store id
     * @param Config $config     The store configuration for $storeId
     * @param Template $template The (generated) template file
     */
    public function writeStoreConfig($storeId, Config $config, Template $template)
    {
        $configCacheItem = $this->cachePool->getItem("store_{$storeId}.config");
        $configCacheItem->set($config);
        $this->cachePool->saveDeferred($configCacheItem);
        $templateCacheItem = $this->cachePool->getItem("store_{$storeId}.template");
        $templateCacheItem->set($template->getFilename());
        $this->cachePool->saveDeferred($templateCacheItem);
    }

    /**
     * @param $storeId
     * @return Config
     */
    public function getConfig($storeId)
    {
        //TODO implement
    }

    /**
     * @param $storeId
     * @return Template
     */
    public function getTemplate($storeId)
    {
        //TODO implement
    }
}