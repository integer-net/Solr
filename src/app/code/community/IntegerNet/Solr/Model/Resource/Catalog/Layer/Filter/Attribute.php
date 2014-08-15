<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Resource_Catalog_Layer_Filter_Attribute extends Mage_Catalog_Model_Resource_Layer_Filter_Attribute
{
    /**
     * Apply attribute filter to product collection
     *
     * @param Mage_Catalog_Model_Layer_Filter_Attribute $filter
     * @param int $value
     * @return Mage_Catalog_Model_Resource_Layer_Filter_Attribute
     */
    public function applyFilterToCollection($filter, $value)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::applyFilterToCollection($filter, $value);
        } 
        
        Mage::getSingleton('integernet_solr/result')->addFilter($filter->getAttributeModel(), $value);
        return $this;
    }
    
    /**
     * Retrieve array with products counts per attribute option
     *
     * @param Mage_Catalog_Model_Layer_Filter_Attribute $filter
     * @return array
     */
    public function getCount($filter)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'catalogsearch') {
            return parent::getCount($filter);
        }
        
        /** @var $solrResult StdClass */
        $solrResult = Mage::getSingleton('integernet_solr/result')->getSolrResult();

        $attribute  = $filter->getAttributeModel();

        $count = array();
        if (isset($solrResult->facet_counts->facet_fields->{$attribute->getAttributeCode() . '_facet'})) {
            foreach((array)$solrResult->facet_counts->facet_fields->{$attribute->getAttributeCode() . '_facet'} as $key => $value) {
                $count[intval($key)] = $value;
            }
            return $count;
        }

        return array();
    }
}