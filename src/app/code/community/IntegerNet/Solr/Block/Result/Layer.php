<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Result_Layer extends Mage_Core_Block_Template
{
    protected $_filters = null;
    
    /**
     * Check availability display layer block
     *
     * @return bool
     */
    public function canShowBlock()
    {
        return true;
    }

    /**
     * Check availability display layer block
     *
     * @return bool
     */
    public function canShowOptions()
    {
        return (bool)sizeof($this->getFilters());
    }
    
    public function getStateHtml()
    {
        return '';
    }
    
    public function getFilters()
    {
        if (is_null($this->_filters)) {
            $this->_filters = array();
            foreach (Mage::helper('integernet_solr')->getFilterableInSearchAttributes(false) as $attribute) {
                /** @var Mage_Catalog_Model_Entity_Attribute $attribute */

                $attributeCodeFacetName = $attribute->getAttributeCode() . '_facet';
                if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName})) {

                    $attributeFacets = (array)$this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName};
                    $this->_filters[] = $this->_getFilter($attribute, $attributeFacets);
                }
            }
        }
        return $this->_filters;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @param int[] $attributeFacets
     * @return Varien_Object
     */
    protected function _getFilter($attribute, $attributeFacets)
    {
        $filter = new Varien_Object();
        $filter->setName($attribute->getStoreLabel());
        $filter->setItemsCount(sizeof($attributeFacets));
        $filter->setHtml(
            $this->getChild('filter')->setData('attribute', $attribute)->toHtml()
        );
        return $filter;
    }

    /**
     * @return Apache_Solr_Response
     */
    protected function _getSolrResult()
    {
        return Mage::getSingleton('integernet_solr/result')->getSolrResult();
    }
}