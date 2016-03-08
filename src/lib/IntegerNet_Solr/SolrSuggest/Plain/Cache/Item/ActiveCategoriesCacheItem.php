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

use IntegerNet\SolrSuggest\Implementor\SerializableSuggestCategory;

final class ActiveCategoriesCacheItem extends AbstractCacheItem
{
    /**
     * @param int $storeId
     * @param SerializableSuggestCategory[] $value
     */
    public function __construct($storeId, array $value = null)
    {
        $this->storeId = $storeId;
        $this->value = $value;
    }

    public function getKey()
    {
        return "store_{$this->storeId}.categories";
    }
}