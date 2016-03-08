<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
if (@class_exists('GoMage_Navigation_Model_Layer_Filter_Price')) {
    class IntegerNet_Solr_Model_Catalog_Layer_Filter_Price_Abstract extends GoMage_Navigation_Model_Layer_Filter_Price
    {}
} else {
    class IntegerNet_Solr_Model_Catalog_Layer_Filter_Price_Abstract extends Mage_Catalog_Model_Layer_Filter_Price
    {}
}

class IntegerNet_Solr_Model_Catalog_Layer_Filter_Price extends IntegerNet_Solr_Model_Catalog_Layer_Filter_Price_Abstract 
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
     * Apply price range filter
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param $filterBlock
     *
     * @return Mage_Catalog_Model_Layer_Filter_Price
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        /**
         * Filter must be string: $fromPrice-$toPrice
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }

        foreach(explode(',', $filter) as $subFilter) {

            //validate filter
            $filterParams = explode(',', $subFilter);
            $subFilter = $this->_validateFilter($filterParams[0]);
            if (!$subFilter) {
                return $this;
            }

            list($from, $to) = $subFilter;

            $this->setInterval(array($from, $to));

            $priorFilters = array();
            for ($i = 1; $i < count($filterParams); ++$i) {
                $priorFilter = $this->_validateFilter($filterParams[$i]);
                if ($priorFilter) {
                    $priorFilters[] = $priorFilter;
                } else {
                    //not valid data
                    $priorFilters = array();
                    break;
                }
            }
            if ($priorFilters) {
                $this->setPriorIntervals($priorFilters);
            }

            $this->_applyPriceRange();
            $this->getLayer()->getState()->addFilter($this->_createItem(
                $this->_renderRangeLabel(empty($from) ? 0 : $from, $to),
                $filter
            ));
        }

        return $this;
    }
}