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

final class ConfigCacheItem extends AbstractCacheItem
{
    /**
     * @param int $storeId
     * @param SerializableConfig $value
     */
    public function __construct($storeId, SerializableConfig $value = null)
    {
        $this->storeId = $storeId;
        $this->value = $value;
    }

    public function getKey()
    {
        return "store_{$this->storeId}.config";
    }

}