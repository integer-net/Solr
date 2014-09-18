<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

if (@class_exists('GoMage_Navigation_Model_Resource_Eav_Mysql4_Layer_Filter_Price')) {
    class IntegerNet_Solr_Model_Resource_Catalog_Layer_Filter_Price_Abstract extends GoMage_Navigation_Model_Resource_Eav_Mysql4_Layer_Filter_Price
    {}
} else {
    class IntegerNet_Solr_Model_Resource_Catalog_Layer_Filter_Price_Abstract extends Mage_Catalog_Model_Resource_Layer_Filter_Price
    {}
}

class IntegerNet_Solr_Model_Resource_Catalog_Layer_Filter_Price extends IntegerNet_Solr_Model_Resource_Catalog_Layer_Filter_Price_Abstract
{
    /**
     * Retrieve maximal price for attribute
     *
     * @param Mage_Catalog_Model_Layer_Filter_Price $filter
     * @return float
     * @todo return correct value
     */
    public function getMaxPrice($filter)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::getMaxPrice($filter);
        }

        return 200;
    }

    /**
     * Retrieve array with products counts per price range
     *
     * @param Mage_Catalog_Model_Layer_Filter_Price $filter
     * @param int $range
     * @return array
     */
    public function getCount($filter, $range)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::getCount($filter, $range);
        }

        return array();
    }
}