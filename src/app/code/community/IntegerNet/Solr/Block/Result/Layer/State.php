<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Result_Layer_State extends Mage_Core_Block_Template
{
    protected $_activeFilters = null;
    
    public function getActiveFilters()
    {
        if (is_null($this->_activeFilters)) {
            $this->_activeFilters = array();

            foreach (Mage::helper('integernet_solr')->getFilterableInSearchAttributes(false) as $attribute) {
                /** @var Mage_Catalog_Model_Entity_Attribute $attribute */

                if ($optionId = Mage::app()->getRequest()->getParam($attribute->getAttributeCode())) {
                    $optionLabel = $attribute->getSource()->getOptionText($optionId);
                    $filter = new Varien_Object();
                    $filter->setAttribute($attribute);
                    $filter->setName($attribute->getStoreLabel());
                    $filter->setLabel($optionLabel);
                    $filter->setRemoveUrl($this->_getRemoveUrl($attribute->getAttributeCode()));
                    $this->_activeFilters[] = $filter;
                }
            }
        }
        
        return $this->_activeFilters;
    }

    /**
     * Get url for remove item from filter
     *
     * @return string
     */
    protected function _getRemoveUrl($attributeCode)
    {
        $query = array($attributeCode => null);
        $params['_current']     = true;
        $params['_use_rewrite'] = true;
        $params['_query']       = $query;
        $params['_escape']      = true;
        return Mage::getUrl('*/*/*', $params);
    }

    /**
     * @return Apache_Solr_Response
     */
    protected function _getSolrResult()
    {
        return Mage::getSingleton('integernet_solr/result')->getSolrResult();
    }
    
    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function getClearUrl()
    {
        $filterState = array();
        foreach ($this->getActiveFilters() as $item) {
            $filterState[$item->getAttribute()->getAttributeCode()] = null;
        }
        $params['_current']     = true;
        $params['_use_rewrite'] = true;
        $params['_query']       = $filterState;
        $params['_escape']      = true;
        return Mage::getUrl('*/*/*', $params);
    }
}