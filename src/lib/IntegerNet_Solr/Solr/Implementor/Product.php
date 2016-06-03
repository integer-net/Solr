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
     * @return int
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

    public function getSearchableAttributeValue(Attribute $attribute);

    public function getCategoryIds();

    /**
     * @return ProductIterator
     */
    public function getChildren();
}