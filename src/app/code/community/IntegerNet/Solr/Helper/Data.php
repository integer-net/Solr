<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Helper_Data extends Mage_Core_Helper_Abstract
{
    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_searchableAttributes = null;

    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_filterableInSearchAttributes = null;

    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_sortableAttributes = null;

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getSearchableAttributes()
    {
        if (is_null($this->_searchableAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_searchableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsSearchableFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;
        }

        return $this->_searchableAttributes;
    }

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getSortableAttributes()
    {
        if (is_null($this->_sortableAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_sortableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('used_for_sort_by', 1)
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;
        }

        return $this->_sortableAttributes;
    }

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInSearchAttributes()
    {
        if (is_null($this->_filterableInSearchAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_filterableInSearchAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsFilterableInSearchFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
                ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC)
            ;
        }

        return $this->_filterableInSearchAttributes;
    }


    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @return string
     */
    public function getFieldName($attribute)
    {
        if ($attribute->getUsedForSortBy()) {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f';

                case 'text':
                    return $attribute->getAttributeCode() . '_t';

                default:
                    return $attribute->getAttributeCode() . '_s';
            }
        } else {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f_mv';

                case 'text':
                    return $attribute->getAttributeCode() . '_t_mv';

                default:
                    return $attribute->getAttributeCode() . '_s_mv';
            }
        }
    }
}