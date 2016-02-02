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
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use Psr\Cache\CacheItemPoolInterface;

class CustomCache
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
     * @param int $storeId  The store id
     * @param Transport $data Custom data (key => value) for $storeId
     */
    public function writeCustomCache($storeId, Transport $data, CustomHelperFactory $customHelperFactory)
    {
        $configCacheItem = $this->cachePool->getItem("store_{$storeId}.custom");
        $configCacheItem->set($data);
        $this->cachePool->saveDeferred($configCacheItem);
        $helperCacheItem = $this->cachePool->getItem("store_{$storeId}.customHelper");
        $helperCacheItem->set($customHelperFactory);
        $this->cachePool->saveDeferred($helperCacheItem);
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getData($path)
    {
        //TODO implement
    }

}