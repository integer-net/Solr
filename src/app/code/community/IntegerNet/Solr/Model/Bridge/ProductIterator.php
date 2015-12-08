<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Product;
use IntegerNet\Solr\Implementor\ProductIterator;

class IntegerNet_Solr_Model_Bridge_ProductIterator extends IteratorIterator implements ProductIterator
{

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $_collection
     */
    public function __construct(Mage_Catalog_Model_Resource_Product_Collection $_collection)
    {
        parent::__construct($_collection);
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getInnerIterator()
    {
        parent::getInnerIterator();
    }

    /**
     * @return Product
     */
    public function current()
    {
        return new IntegerNet_Solr_Model_Bridge_Product($this->getInnerIterator()->current());
    }

}