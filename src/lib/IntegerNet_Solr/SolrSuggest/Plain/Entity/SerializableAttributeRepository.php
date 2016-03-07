<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Entity;

/**
 * Retrieves serialized attributes for cache
 */
interface SerializableAttributeRepository
{
    /**
     * @param int $storeId
     * @return SerializableAttribute[]
     */
    public function findFilterableInSearchAttributes($storeId);

    /**
     * @param $storeId
     * @return SerializableAttribute[]
     */
    public function findSearchableAttributes($storeId);
}