<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Result_Layer_View extends Mage_Core_Block_Template
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
        return $this->getChildHtml('state');
    }
    
    public function getFilters()
    {
        if (is_null($this->_filters)) {
            $this->_filters = array();
            $facetName = 'category';
            if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$facetName})) {

                $categoryFacets = (array)$this->_getSolrResult()->facet_counts->facet_fields->{$facetName};
                $categoryFilter = $this->_getCategoryFilter($categoryFacets);
                if ($categoryFilter->getHtml()) {
                    $this->_filters[] = $categoryFilter;
                }
            }
            foreach (Mage::helper('integernet_solr')->getFilterableAttributes(false) as $attribute) {
                /** @var Mage_Catalog_Model_Entity_Attribute $attribute */

                /** @var Mage_Catalog_Model_Category $currentCategory */
                $currentCategory = $this->_getCurrentCategory();
                if ($currentCategory) {
                    $removedFilterAttributeCodes = $currentCategory->getData('solr_remove_filters');
                    
                    if (is_array($removedFilterAttributeCodes) && in_array($attribute->getAttributeCode(), $removedFilterAttributeCodes)) {
                        continue;
                    }
                }

                $attributeCodeFacetName = $attribute->getAttributeCode() . '_facet';
                if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName})) {

                    $attributeFacets = (array)$this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName};
                    $this->_filters[] = $this->_getFilter($attribute, $attributeFacets);
                }
                $attributeCodeFacetRangeName = Mage::helper('integernet_solr')->getFieldName($attribute);
                if (isset($this->_getSolrResult()->facet_counts->facet_intervals->{$attributeCodeFacetRangeName})) {

                    $attributeFacetData = (array)$this->_getSolrResult()->facet_counts->facet_intervals->{$attributeCodeFacetRangeName};
                    $this->_filters[] = $this->_getIntervalFilter($attribute, $attributeFacetData);
                } elseif (isset($this->_getSolrResult()->facet_counts->facet_ranges->{$attributeCodeFacetRangeName})) {

                    $attributeFacetData = (array)$this->_getSolrResult()->facet_counts->facet_ranges->{$attributeCodeFacetRangeName};
                    $this->_filters[] = $this->_getRangeFilter($attribute, $attributeFacetData);
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
            $this->getChild('filter')
                ->setData('is_category', false)
                ->setData('is_range', false)
                ->setData('attribute', $attribute)
                ->toHtml()
        );
        return $filter;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @param array $attributeFacetData
     * @return Varien_Object
     */
    protected function _getIntervalFilter($attribute, $attributeFacetData)
    {
        $filter = new Varien_Object();
        $filter->setName($attribute->getStoreLabel());
        $filter->setItemsCount(sizeof($attributeFacetData));
        $filter->setHtml(
            $this->getChild('filter')
                ->setData('is_category', false)
                ->setData('is_range', true)
                ->setData('attribute', $attribute)
                ->toHtml()
        );
        return $filter;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @param array $attributeFacetData
     * @return Varien_Object
     */
    protected function _getRangeFilter($attribute, $attributeFacetData)
    {
        $filter = new Varien_Object();
        $filter->setName($attribute->getStoreLabel());
        $filter->setItemsCount(sizeof($attributeFacetData['counts']));
        $filter->setHtml(
            $this->getChild('filter')
                ->setData('is_category', false)
                ->setData('is_range', true)
                ->setData('attribute', $attribute)
                ->toHtml()
        );
        return $filter;
    }

    /**
     * @param int[] $categoryFacets
     * @return Varien_Object
     */
    protected function _getCategoryFilter($categoryFacets)
    {
        $filter = new Varien_Object();
        $filter->setName(Mage::helper('catalog')->__('Category'));
        $filter->setItemsCount(sizeof($categoryFacets));
        
        /** @var IntegerNet_Solr_Block_Result_Layer_Filter $filterBlock */
        $filterBlock = $this->getChild('filter')
            ->setData('is_category', true);
        if (sizeof($filterBlock->getItems())) {
            $filter->setHtml(
                $filterBlock->toHtml()
            );
        } else {
            $filter->setHtml('');
        }
        return $filter;
    }

    /**
     * @return Apache_Solr_Response
     */
    protected function _getSolrResult()
    {
        return Mage::getSingleton('integernet_solr/result')->getSolrResult();
    }

    /**
     * @return IntegerNet_Solr_Block_Result_Layer
     */
    public function getLayer()
    {
        return $this->getLayout()->createBlock('integernet_solr/result_layer');
    }
    
    /**
     * @return Mage_Catalog_Model_Category|false
     */
    protected function _getCurrentCategory()
    {
        if (is_null($this->_currentCategory)) {
            if ($filteredCategoryId = Mage::app()->getRequest()->getParam('cat')) {
                /** @var Mage_Catalog_Model_Category $currentCategory */
                $this->_currentCategory = Mage::getModel('catalog/category')->load($filteredCategoryId);
            } else {
                /** @var Mage_Catalog_Model_Category $currentCategory */
                $this->_currentCategory = Mage::registry('current_category');
                if (is_null($this->_currentCategory)) {
                    $this->_currentCategory = false;
                }
            }
        }

        return $this->_currentCategory;
    }
}