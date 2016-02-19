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
 * Retrieves serializable categories for cache
 *
 * @todo extract implementation and move to Plain/Entity
 * @see SerializableAttributeRepository
 */
interface SerializableCategoryRepository
{
    /**
     * @param int $storeId
     * @return SerializableCategory[]
     */
    public function findActiveCategories($storeId);
}