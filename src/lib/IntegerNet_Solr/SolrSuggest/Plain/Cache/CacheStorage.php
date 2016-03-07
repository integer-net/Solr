<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache;

/**
 * Simple read/write interface for cache
 */
interface CacheStorage
{
    /**
     * @param CacheItem $item
     */
    public function save(CacheItem $item);

    /**
     * @param CacheItem $item
     * @return CacheItem
     * @throws CacheItemNotFoundException
     */
    public function load(CacheItem $item);
}