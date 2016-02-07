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
     * @var Cache
     */
    private $cache;

    /**
     * ConfigCache constructor.
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param int $storeId  The store id
     * @param Transport $data Custom data (key => value) for $storeId
     */
    public function writeCustomCache($storeId, Transport $data, CustomHelperFactory $customHelperFactory)
    {
        $this->cache->save($this->getCustomDataCacheKey($storeId), $data);
        $this->cache->save($this->getCustomHelperCacheKey($storeId), $customHelperFactory);
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getData($path)
    {
        //TODO implement
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getCustomDataCacheKey($storeId)
    {
        return "store_{$storeId}.custom";
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getCustomHelperCacheKey($storeId)
    {
        return "store_{$storeId}.customHelper";
    }

}