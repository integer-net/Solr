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

use IntegerNet\SolrSuggest\Plain\Cache\CacheItem;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Adapter for PSR-6 cache backends
 */
final class PsrCache implements CacheStorage
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
     * @param CacheItem $item
     */
    public function save(CacheItem $item)
    {
        $key = $item->getKey();
        try {
            $cacheItem = $this->cachePool->getItem($key);
        } catch (InvalidCacheItemValueException $e) {
            $this->cachePool->deleteItem($key);
            $cacheItem = $this->cachePool->getItem($key);
        }
        $cacheItem->set($item->getValueForCache());
        $this->cachePool->saveDeferred($cacheItem);
    }

    /**
     * @param CacheItem $item
     * @return CacheItem
     * @throws CacheItemNotFoundException
     */
    public function load(CacheItem $item)
    {
        $key = $item->getKey();
        if (!$this->cachePool->hasItem($key)) {
            throw new CacheItemNotFoundException("Cache item {$key} not found");
        }
        $cacheItem = $this->cachePool->getItem($key);
        return $item->withValueFromCache($cacheItem->get());
    }
}