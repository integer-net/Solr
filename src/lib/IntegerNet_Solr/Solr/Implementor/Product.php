<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Implementor;

interface Product
{
    /**
     * @return string unique identifier for product data (id X store)
     */
    public function getSolrId();
    /**
     * @return bool
     */
    public function isIndexable();

    public function getId();

    public function getStoreId();

    public function isVisibleInCatalog();

    public function isVisibleInSearch();

    public function hasSpecialPrice();

    public function getSolrBoost();

    public function getPrice();

    public function getAttributeValue(Attribute $attribute);

    /**
     * Return searchable attribute value (localized values instead of ids, comma separated strings instead of arrays)
     *
     * @param Attribute $attribute
     * @return string|null
     */
    public function getSearchableAttributeValue(Attribute $attribute);

    public function getCategoryIds();

    /**
     * @deprecated use ProductRepository::getChildProducts() instead
     * @return ProductIterator
     */
    public function getChildren();
}