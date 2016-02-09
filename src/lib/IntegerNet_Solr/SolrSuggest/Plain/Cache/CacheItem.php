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

interface CacheItem
{
    /**
     * Return cache key
     *
     * @return string
     */
    public function getKey();

    /**
     * Return serializable value for cache
     *
     * @return mixed
     */
    public function getValueForCache();

    /**
     * Return new instance based on value in cache
     *
     * @param $value value from cache
     * @return CacheItem
     */
    public function withValueFromCache($value);

    /**
     * Return value
     *
     * @return mixed
     */
    public function getValue();
}