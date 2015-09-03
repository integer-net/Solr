<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Result_Layer_Filter extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Catalog_Model_Entity_Attribute
     */
    public function getAttribute()
    {
        return $this->getData('attribute');
    }

    /**
     * @return Varien_Object[]
     * @throws Mage_Core_Exception
     */
    public function getItems()
    {
        $items = array();
        
        $attributeCodeFacetName = $this->getAttribute()->getAttributeCode() . '_facet';
        if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName})) {

            $attributeFacets = (array)$this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName};

            foreach($attributeFacets as $optionId => $optionCount) {
                $item = new Varien_Object();
                $item->setCount($optionCount);
                $item->setLabel($this->getAttribute()->getSource()->getOptionText($optionId));
                $item->setUrl($this->_getUrl($optionId));
                $items[] = $item;
            }
        }
        
        return $items;
    }

    /**
     * Get filter item url
     *
     * @param int $optionId
     * @return string
     */
    protected function _getUrl($optionId)
    {
        $query = array(
            $this->getAttribute()->getAttributeCode() => $optionId,
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
        return Mage::getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true, '_query'=>$query));
    }

    /**
     * @return Apache_Solr_Response
     */
    protected function _getSolrResult()
    {
        return Mage::getSingleton('integernet_solr/result')->getSolrResult();
    }
}