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

use Psr\Cache\CacheItemPoolInterface;

/**
 * Adapter for PSR-6 cache backends
 *
 * @package IntegerNet\SolrSuggest\Plain\Cache
 */
final class PsrCache implements Cache
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
     * @param $key
     * @param $value
     */
    public function save($key, $value)
    {
        $item = $this->cachePool->getItem($key);
        $item->set($value);
        $this->cachePool->saveDeferred($item);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function load($key)
    {
        if (!$this->cachePool->hasItem($key)) {
            throw new CacheItemNotFoundException("Cache item {$key} not found");
        }
        $item = $this->cachePool->getItem($key);
        return $item->get();
    }
}