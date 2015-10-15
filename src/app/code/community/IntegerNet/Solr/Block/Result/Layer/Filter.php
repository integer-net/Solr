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
        $items = array();

        if ($this->isCategory()) {
            $facetName = 'category';
            if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$facetName})) {

                $categoryFacets = (array)$this->_getSolrResult()->facet_counts->facet_fields->{$facetName};

                foreach($categoryFacets as $optionId => $optionCount) {
                    $item = new Varien_Object();
                    $item->setCount($optionCount);
                    $item->setLabel(Mage::getResourceSingleton('catalog/category')->getAttributeRawValue($optionId, 'name', Mage::app()->getStore()));
                    $item->setUrl($this->_getUrl($optionId));
                    $items[] = $item;
                }
            }
            return $items;
        }

        if ($this->isRange()) {
            $store = Mage::app()->getStore();
            $attributeCodeFacetRangeName = Mage::helper('integernet_solr')->getFieldName($this->getAttribute());
            if (isset($this->_getSolrResult()->facet_counts->facet_intervals->{$attributeCodeFacetRangeName})) {

                $attributeFacetData = (array)$this->_getSolrResult()->facet_counts->facet_intervals->{$attributeCodeFacetRangeName};

                $i = 0;
                foreach($attributeFacetData as $range => $rangeCount) {
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
                        $label = Mage::helper('catalog')->__('%s - %s', $store->formatPrice($rangeStart),  $store->formatPrice($rangeEnd - 0.01));
                    }

                    $item->setLabel($label);
                    $item->setUrl($this->_getIntervalUrl($i));
                    $items[] = $item;
                }
            } elseif (isset($this->_getSolrResult()->facet_counts->facet_ranges->{$attributeCodeFacetRangeName})) {

                $attributeFacetData = (array)$this->_getSolrResult()->facet_counts->facet_ranges->{$attributeCodeFacetRangeName};

                foreach($attributeFacetData['counts'] as $rangeStart => $rangeCount) {
                    $item = new Varien_Object();
                    $item->setCount($rangeCount);
                    $rangeEnd = $rangeStart + $attributeFacetData['gap'];
                    $item->setLabel(Mage::helper('catalog')->__(
                        '%s - %s',
                        $store->formatPrice($rangeStart),
                        $store->formatPrice($rangeEnd - 0.01)
                    ));
                    $item->setUrl($this->_getRangeUrl($rangeStart, $rangeEnd));
                    $items[] = $item;
                }
            }
        }

        $attributeCodeFacetName = $this->getAttribute()->getAttributeCode() . '_facet';
        if (isset($this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName})) {

            $attributeFacets = (array)$this->_getSolrResult()->facet_counts->facet_fields->{$attributeCodeFacetName};

            foreach($attributeFacets as $optionId => $optionCount) {
                if (!$optionCount) {
                    continue;
                }
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
        if ($this->isCategory()) {
            $query = array(
                'cat' => $optionId,
                Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
            );
        } else {
            $query = array(
                $this->getAttribute()->getAttributeCode() => $optionId,
                Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
            );
        }
        return Mage::getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true, '_query'=>$query));
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
        $query = array(
            $this->getAttribute()->getAttributeCode() => floatval($rangeStart) . '-' . floatval($rangeEnd),
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
        return Mage::getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true, '_query'=>$query));
    }

    /**
     * Get filter item url
     *
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return string
     */
    protected function _getIntervalUrl($index)
    {
        $query = array(
            $this->getAttribute()->getAttributeCode() => $index . ',100',
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
}