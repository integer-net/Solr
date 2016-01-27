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
     * Whether to display product count for layer navigation items
     * @var bool
     */
    protected $_displayProductCount = null;

    protected $_categoryFilterItems = null;

    protected $_currentCategory = null;

    /**
     * @return Mage_Catalog_Model_Entity_Attribute
     */
    public function getAttribute()
    {
        return $this->getData('attribute');
    }

    public function isCategory()
    {
        return (boolean)$this->getData('is_category');
    }

    public function isRange()
    {
        return (boolean)$this->getData('is_range');
    }

    /**
     * @return Varien_Object[]
     * @throws Mage_Core_Exception
     */
    public function getItems()
    {
        if ($this->isCategory()) {
            return $this->_getCategoryFilterItems();
        }

        if ($this->isRange()) {
            return $this->_getRangeFilterItems();
        }

        return $this->_getAttributeFilterItems();
    }

    /**
     * Get filter item url
     *
     * @param int $optionId
     * @return string
     */
    protected function _getUrl($optionId)
    {
        if ($this->isCategory()) {
            $identifier = 'cat';
        } else {
            $identifier = $this->getAttribute()->getAttributeCode();
        }
        $query = $this->_getQuery($identifier, $optionId);
        return Mage::getUrl('*/*/*', array('_current' => true, '_use_rewrite' => true, '_query' => $query));
    }

    /**
     * Get filter item url
     *
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return string
     */
    protected function _getRangeUrl($rangeStart, $rangeEnd)
    {
        $identifier = 'price';
        $query = $this->_getQuery($identifier, floatval($rangeStart) . '-' . floatval($rangeEnd));
        return Mage::getUrl('*/*/*', array('_current' => true, '_use_rewrite' => true, '_query' => $query));
    }

    /**
     * @return Apache_Solr_Response
     */
    protected function _getSolrResult()
    {
        return Mage::getSingleton('integernet_solr/result')->getSolrResult();
    }

    /**
     * Getter for $_displayProductCount
     * @return bool
     */
    public function shouldDisplayProductCount()
    {
        if ($this->_displayProductCount === null) {
            $this->_displayProductCount = Mage::helper('catalog')->shouldDisplayProductCountOnLayer();
        }
        return $this->_displayProductCount;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Category_Collection
     * @throws Mage_Core_Exception
     */
    protected function _getCurrentChildrenCategories()
    {
        $currentCategory = $this->_getCurrentCategory();

        $childrenCategories = Mage::getResourceModel('catalog/category_collection')
            ->setStore(Mage::app()->getStore())
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('level', $currentCategory->getLevel() + 1)
            ->addAttributeToFilter('path', array('like' => $currentCategory->getPath() . '_%'))
            ->setOrder('position', 'asc');

        return $childrenCategories;
    }

    /**
     * @return Varien_Object[]
     */
    protected function _getCategoryFilterItems()
    {
        if (is_null($this->_categoryFilterItems)) {

            $facetName = 'category';
            if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$facetName})) {

                $categoryFacets = $this->_getSolrResult()->facet_counts->facet_fields->{$facetName};

                if (Mage::helper('integernet_solr')->isCategoryPage()) {

                    $childrenCategories = $this->_getCurrentChildrenCategories();

                    foreach ($childrenCategories as $childCategory) {
                        $childCategoryId = $childCategory->getId();
                        if (isset($categoryFacets->{$childCategoryId})) {
                            $item = new Varien_Object();
                            $item->setCount($categoryFacets->{$childCategoryId});
                            $item->setLabel($this->_getCheckboxHtml('cat', $childCategoryId) . ' ' . $childCategory->getName());
                            $item->setUrl($this->_getUrl($childCategoryId));
                            $this->_categoryFilterItems[] = $item;
                        }
                    }

                } else {

                    foreach ((array)$categoryFacets as $optionId => $optionCount) {
                        $item = new Varien_Object();
                        $item->setCount($optionCount);
                        $item->setLabel($this->_getCheckboxHtml('cat', $optionId) . ' ' . Mage::getResourceSingleton('catalog/category')->getAttributeRawValue($optionId, 'name', Mage::app()->getStore()));
                        $item->setUrl($this->_getUrl($optionId));
                        $this->_categoryFilterItems[] = $item;
                    }
                }
            }
        }

        return $this->_categoryFilterItems;
    }

    /**
     * @return Varien_Object[]
     */
    protected function _getRangeFilterItems()
    {
        $items = array();

        $store = Mage::app()->getStore();
        $attributeCodeFacetRangeName = Mage::helper('integernet_solr')->getFieldName($this->getAttribute());
        if (isset($this->_getSolrResult()->facet_counts->facet_intervals->{$attributeCodeFacetRangeName})) {

            $attributeFacetData = (array)$this->_getSolrResult()->facet_counts->facet_intervals->{$attributeCodeFacetRangeName};

            $i = 0;
            foreach ($attributeFacetData as $range => $rangeCount) {
                $i++;
                if (!$rangeCount) {
                    continue;
                }

                $item = new Varien_Object();
                $item->setCount($rangeCount);

                $commaPos = strpos($range, ',');
                $rangeStart = floatval(substr($range, 1, $commaPos - 1));
                $rangeEnd = floatval(substr($range, $commaPos + 1, -1));
                if ($rangeEnd == 0) {
                    $label = Mage::helper('integernet_solr')->__('from %s', $store->formatPrice($rangeStart));
                } else {
                    $label = Mage::helper('catalog')->__('%s - %s', $store->formatPrice($rangeStart), $store->formatPrice($rangeEnd));
                }

                $item->setLabel($this->_getCheckboxHtml('price', floatval($rangeStart) . '-' . floatval($rangeEnd)) . ' ' . $label);
                $item->setUrl($this->_getRangeUrl($rangeStart, $rangeEnd));
                $items[] = $item;
            }
        } elseif (isset($this->_getSolrResult()->facet_counts->facet_ranges->{$attributeCodeFacetRangeName})) {

            $attributeFacetData = (array)$this->_getSolrResult()->facet_counts->facet_ranges->{$attributeCodeFacetRangeName};

            foreach ($attributeFacetData['counts'] as $rangeStart => $rangeCount) {
                $item = new Varien_Object();
                $item->setCount($rangeCount);
                $rangeEnd = $rangeStart + $attributeFacetData['gap'];
                $item->setLabel($this->_getCheckboxHtml('price', floatval($rangeStart) . '-' . floatval($rangeEnd)) . ' ' . Mage::helper('catalog')->__(
                    '%s - %s',
                    $store->formatPrice($rangeStart),
                    $store->formatPrice($rangeEnd)
                ));
                $item->setUrl($this->_getRangeUrl($rangeStart, $rangeEnd));
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * @return Varien_Object[]
     * @throws Mage_Core_Exception
     */
    protected function _getAttributeFilterItems()
    {
        $items = array();
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $attributeCodeFacetName = $attributeCode . '_facet';
        if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName})) {

            $attributeFacets = (array)$this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName};

            foreach ($attributeFacets as $optionId => $optionCount) {
                if (!$optionCount) {
                    continue;
                }
                /** @var Mage_Catalog_Model_Category $currentCategory */
                $currentCategory = $this->_getCurrentCategory();
                if ($currentCategory) {
                    $removedFilterAttributeCodes = $currentCategory->getData('solr_remove_filters');
                    if (is_array($removedFilterAttributeCodes) && in_array($attributeCode, $removedFilterAttributeCodes)) {
                        continue;
                    }
                }
                $item = new Varien_Object();
                $item->setCount($optionCount);
                $item->setLabel($this->_getCheckboxHtml($attributeCode, $optionId) . ' ' . $this->getAttribute()->getSource()->getOptionText($optionId));
                $item->setUrl($this->_getUrl($optionId));
                $items[] = $item;
            }
        }

        return $items;
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

    protected function _getCheckboxHtml($attributeCode, $optionId)
    {
        /** @var $checkboxBlock IntegerNet_Solr_Block_Result_Layer_Checkbox */
        $checkboxBlock = $this->getLayout()->createBlock('integernet_solr/result_layer_checkbox');
        $checkboxBlock
            ->setIsChecked($this->_isSelected($attributeCode, $optionId))
            ->setOptionId($optionId)
            ->setAttributeCode($attributeCode);
        return $checkboxBlock->toHtml();
    }

    /**
     * @param string $identifier
     * @param int $optionId
     * @return bool
     */
    protected function _isSelected($identifier, $optionId)
    {
        $selectedOptionIds = explode(',', $this->_getCurrentParamValue($identifier));
        if (in_array($optionId, $selectedOptionIds)) {
            return true;
        }
        return false;
    }

    /**
     * Get updated query params, depending on previously selected filters
     *
     * @param string $identifier
     * @param int $optionId
     * @return array
     */
    protected function _getQuery($identifier, $optionId)
    {
        $currentParamValue = $this->_getCurrentParamValue($identifier);
        if (strlen($currentParamValue)) {
            $selectedOptionIds = explode(',', $currentParamValue);
        } else {
            $selectedOptionIds = array();
        }
        if (in_array($optionId, $selectedOptionIds)) {
            $newParamValues = array_diff($selectedOptionIds, array($optionId));
        } else {
            $newParamValues = $selectedOptionIds;
            $newParamValues[] = $optionId;
        }
        if (sizeof($newParamValues)) {
            $newParamValues = implode(',', $newParamValues);
        } else {
            $newParamValues = null;
        }
        return array(
            $identifier => $newParamValues,
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
    }

    /**
     * @param $identifier
     * @return mixed
     */
    protected function _getCurrentParamValue($identifier)
    {
        return Mage::app()->getRequest()->getParam($identifier);
    }
}