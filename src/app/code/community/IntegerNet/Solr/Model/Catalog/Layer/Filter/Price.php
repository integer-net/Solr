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
        if (!Mage::helper('integernet_solr')->isActive()) {
            return parent::getPriceRange();
        }

        if (Mage::app()->getRequest()->getModuleName() != 'catalogsearch' && !Mage::helper('integernet_solr')->isCategoryPage()) {
            return parent::getPriceRange();
        }

        return Mage::getStoreConfig('integernet_solr/results/price_step_size');
    }

    /**
     * Prepare text of item label
     *
     * @param   int $range
     * @param   float $value
     * @return  string
     */
    protected function _renderItemLabel($range, $value)
    {
        if (!Mage::helper('integernet_solr')->isActive()) {
            return parent::_renderItemLabel($range, $value);
        }

        if (Mage::app()->getRequest()->getModuleName() != 'catalogsearch' && !Mage::helper('integernet_solr')->isCategoryPage()) {
            return parent::_renderItemLabel($range, $value);
        }

        $store = Mage::app()->getStore();
        
        if (Mage::getStoreConfigFlag('integernet_solr/results/use_custom_price_intervals')
            && $customPriceIntervals = Mage::getStoreConfig('integernet_solr/results/custom_price_intervals')) {
            $lowerBorder = 0;
            $i = 1;
            foreach (explode(',', $customPriceIntervals) as $upperBorder) {
                if ($i == $value) {
                    return Mage::helper('catalog')->__('%s - %s', $store->formatPrice($lowerBorder), $store->formatPrice($upperBorder - 0.01));
                    break;
                }

                $i++;
                $lowerBorder = $upperBorder;
            }
            return Mage::helper('integernet_solr')->__('from %s', $store->formatPrice($lowerBorder));
        }

        return parent::_renderItemLabel($range, $value);
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
        if (!Mage::helper('integernet_solr')->isActive()) {
            return parent::_applyToCollection($range, $index);
        }

        if (Mage::app()->getRequest()->getModuleName() != 'catalogsearch' && !Mage::helper('integernet_solr')->isCategoryPage()) {
            return parent::_applyToCollection($range, $index);
        }

        Mage::getSingleton('integernet_solr/result')->addPriceRangeFilterByIndex($range, $index); 
        
        return $this;
    }
}