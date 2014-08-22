<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Resource_Catalog_Layer_Filter_Price extends Mage_Catalog_Model_Resource_Layer_Filter_Price 
{
    /**
     * Retrieve maximal price for attribute
     *
     * @param Mage_Catalog_Model_Layer_Filter_Price $filter
     * @return float
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