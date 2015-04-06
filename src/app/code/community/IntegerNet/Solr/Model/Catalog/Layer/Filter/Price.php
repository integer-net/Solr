<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Catalog_Layer_Filter_Price extends Mage_Catalog_Model_Layer_Filter_Price 
{
    /**
     * Get price range for building filter steps
     *
     * @return int
     */
    public function getPriceRange()
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active') || Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::getPriceRange();
        }

        return Mage::getStoreConfig('integernet_solr/results/price_step_size');
    }

    /**
     * Apply filter value to product collection based on filter range and selected value
     *
     * @param int $range
     * @param int $index
     * @return Mage_Catalog_Model_Layer_Filter_Price
     */
    protected function _applyToCollection($range, $index)
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active') || Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::_applyToCollection($range, $index);
        }

        Mage::getSingleton('integernet_solr/result')->addPriceRangeFilter($range, $index); 
        
        return $this;
    }
}