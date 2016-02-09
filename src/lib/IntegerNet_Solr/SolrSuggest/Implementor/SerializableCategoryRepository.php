<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Implementor;

/**
 * Retrieves serielizable categories for cache
 */
interface SerializableCategoryRepository
{
    /**
     * @param int $storeId
     * @return SerializableCategory[]
     */
    public function findActiveCategories($storeId);
}