<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

/**
 * This class is a low weight replacement for the "Mage_Core_Model_Store" class in autosuggest calls
 *
 * Class IntegerNet_Solr_Autosuggest_Helper
 */
final class IntegerNet_Solr_Autosuggest_Helper
{
    protected $_query;

    public function getQuery()
    {
        if (is_null($this->_query)) {
            $this->_query = new IntegerNet_Solr_Autosuggest_Query();
        }

        return $this->_query;
    }

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     * @todo adjust
     */
    public function getFilterableInSearchAttributes()
    {
        return array();

        /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
        return Mage::getResourceModel('catalog/product_attribute_collection')
            ->addIsFilterableInSearchFilter()
            ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC)
            ;
    }

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     * @todo adjust
     */
    public function getSearchableAttributes()
    {
        return array();

        /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
        return Mage::getResourceModel('catalog/product_attribute_collection')
            ->addIsSearchableFilter()
            ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @return string
     * @todo adjust
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

    public function getQueryText()
    {
        return $_GET['q'];
    }
}