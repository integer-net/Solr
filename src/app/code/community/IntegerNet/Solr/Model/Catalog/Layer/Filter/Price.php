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
        if (!Mage::helper('integernet_solr')->isActive() || Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
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
        if (!Mage::helper('integernet_solr')->isActive() || Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::_renderItemLabel($range, $value);
        }

        /** @var Apache_Solr_Response $result */
        $result = Mage::getSingleton('integernet_solr/result')->getSolrResult();
        if (isset($result->facet_counts->facet_intervals->price_f)) {

            $intervals = (array)$result->facet_counts->facet_intervals->price_f;
            $store = Mage::app()->getStore();

            $i = 1;
            foreach($intervals as $borders => $count) {
                
                if ($value != $i) {
                    $i++;
                    continue;
                }

                $commaPos = strpos($borders, ',');
                if ($commaPos === false) {
                    return parent::_renderItemLabel($range, $value);
                }

                $fromPrice = floatval(substr($borders, 1, $commaPos - 1));
                $toPrice = floatval(substr($borders, $commaPos + 1, -1));

                if ($toPrice == 0) {
                    return Mage::helper('integernet_solr')->__('from %s', $store->formatPrice($fromPrice));
                } else {
                    return Mage::helper('catalog')->__('%s - %s', $store->formatPrice($fromPrice),  $store->formatPrice($toPrice));
                }
            }
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
        if (!Mage::helper('integernet_solr')->isActive() || Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::_applyToCollection($range, $index);
        }

        Mage::getSingleton('integernet_solr/result')->addPriceRangeFilterByIndex($range, $index); 
        
        return $this;
    }
}