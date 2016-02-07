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

interface Cache
{
    /**
     * @param $key
     * @param $value
     */
    public function save($key, $value);

    /**
     * @param $key
     * @return mixed
     * @throw CacheItemNotFoundException
     */
    public function load($key);
}