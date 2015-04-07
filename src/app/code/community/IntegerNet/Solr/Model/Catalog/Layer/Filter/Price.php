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
     * Get data for build price filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active') || Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::_getItemsData();
        }

        /** @var Apache_Solr_Response $result */
        $result = Mage::getSingleton('integernet_solr/result')->getSolrResult();
        if (!isset($result->facet_counts->facet_intervals->price_f)) {
            return parent::_getItemsData();
        }

        $intervals = (array)$result->facet_counts->facet_intervals->price_f;
        $data = array();
        $store = Mage::app()->getStore();
        
        foreach($intervals as $borders => $count) {
            if (!$count) {
                continue;
            }
            
            $commaPos = strpos($borders, ',');
            if ($commaPos === false) {
                continue;
            }
            
            $fromPrice = floatval(substr($borders, 1, $commaPos - 1));
            $toPrice = floatval(substr($borders, $commaPos + 1, -1));

            if ($toPrice == 0) {
                $data[] = array(
                    'label' => Mage::helper('integernet_solr')->__('from %s', $store->formatPrice($fromPrice)),
                    'value' => $fromPrice . '-',
                    'count' => $count,
                );
            } else {
                $data[] = array(
                    'label' => Mage::helper('catalog')->__('%s - %s', $store->formatPrice($fromPrice),  $store->formatPrice($toPrice)),
                    'value' => $fromPrice . '-' . $toPrice,
                    'count' => $count,
                );
            }
        }
        
        return $data;
    }
    
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
     * Apply price range filter to collection
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param $filterBlock
     *
     * @return Mage_Catalog_Model_Layer_Filter_Price
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        /**
         * Filter must be string: $index,$range
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }

        $fromToFilter = explode('-', $filter);
        if (count($fromToFilter) != 2) {
            return parent::apply($request, $filterBlock);
        }

        list($minBorder, $maxBorder) = $fromToFilter;

        Mage::getSingleton('integernet_solr/result')->addPriceRangeFilterByMinMax($minBorder, $maxBorder);

        return $this;
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

        Mage::getSingleton('integernet_solr/result')->addPriceRangeFilterByIndex($range, $index); 
        
        return $this;
    }
}