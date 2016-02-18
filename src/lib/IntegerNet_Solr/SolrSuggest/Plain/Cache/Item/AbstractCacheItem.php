<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache\Item;

use IntegerNet\Solr\Implementor\SerializableConfig;
use IntegerNet\SolrSuggest\Plain\Cache\CacheItem;

abstract class AbstractCacheItem implements CacheItem
{
    /**
     * @var int
     */
    protected $storeId;
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param int $storeId
     * @param mixed $value
     */
    public function __construct($storeId, $value = null)
    {
        $this->storeId = $storeId;
        $this->value = $value;
    }

    public function getValueForCache()
    {
        return $this->getValue();
    }

    public static function createEmpty($storeId)
    {
        return new static($storeId, null);
    }

    /**
     * @param mixed $value
     * @return static
     */
    public function withValueFromCache($value)
    {
        return new static($this->storeId, $value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}