<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
if (@class_exists('GoMage_Navigation_Model_Search_Layer')) {
    class IntegerNet_Solr_Model_CatalogSearch_Layer_Abstract extends GoMage_Navigation_Model_Search_Layer
    {}
} else {
    class IntegerNet_Solr_Model_CatalogSearch_Layer_Abstract extends Mage_CatalogSearch_Model_Layer
    {}
}

class IntegerNet_Solr_Model_CatalogSearch_Layer extends IntegerNet_Solr_Model_CatalogSearch_Layer_Abstract
{
    /**
     * Get current layer product collection
     *
     * @return Varien_Data_Collection
     */
    public function getProductCollection()
    {
        if (!Mage::helper('integernet_solr')->isActive()) {
            return parent::getProductCollection();
        }

        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = Mage::getModel('integernet_solr/result_collection');
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        return $collection;
    }

    /**
     * Get collection of all filterable attributes for layer products set
     *
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getFilterableAttributes()
    {
        if (!Mage::helper('integernet_solr')->isActive()) {
            return parent::getFilterableAttributes();
        }

        /** @var $collection Mage_Catalog_Model_Resource_Product_Attribute_Collection */
        $collection = Mage::getResourceModel('catalog/product_attribute_collection');
        $collection
            ->setItemObjectClass('catalog/resource_eav_attribute')
            ->addStoreLabel(Mage::app()->getStore()->getId())
            ->addIsFilterableInSearchFilter()
            ->setOrder('position', 'ASC');
        $collection = $this->_prepareAttributeCollection($collection);
        $collection->load();

        return $collection;
    }

}