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

use IntegerNet\Solr\Indexer\IndexDocument;

interface Product extends IndexDocument
{
    public function getId();

    public function getStoreId();

    public function isVisibleInCatalog();

    public function isVisibleInSearch();

    public function getSolrBoost();

    public function getPrice();

    public function getAttributeValue(Attribute $attribute);

    public function getSearchableAttributeValue(\Mage_Catalog_Model_Resource_Eav_Attribute $attribute);

    public function getCategoryIds();

    /**
     * @return ProductIterator
     */
    public function getChildren();
}