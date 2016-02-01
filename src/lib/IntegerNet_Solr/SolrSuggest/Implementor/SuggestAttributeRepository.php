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

interface SuggestAttributeRepository
{
    /**
     * @param string $attributeCode
     * @return SerializableAttribute
     */
    public function getAttributeByCode($attributeCode);

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