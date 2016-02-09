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

use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use IntegerNet\SolrSuggest\Plain\Cache\CacheItem;

final class CustomHelperCacheItem extends AbstractCacheItem
{
    /**
     * @param int $storeId
     * @param CustomHelperFactory $value
     */
    public function __construct($storeId, CustomHelperFactory $value = null)
    {
        $this->storeId = $storeId;
        $this->value = $value;
    }

    public function getKey()
    {
        return "store_{$this->storeId}.customHelper";
    }
}