<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Autosuggest extends Mage_Core_Block_Template
{
    protected $_attributes = array();

    protected function _construct()
    {
        $this->setTemplate('integernet/solr/autosuggest.phtml');
    }

    /**
     * @return array
     */
    public function getSearchwordSuggestions()
    {
        $collection = Mage::getModel('integernet_solr/suggestion_collection');
        $query = $this->getQuery();
        $counter = 1;
        mb_internal_encoding('UTF-8');
        $title = mb_strtolower(trim($this->escapeHtml($query)));
        $data = array(
            array(
                'title' => $title,
                'row_class' => 'odd',
                'num_of_results' => '',
                'url' => Mage::getUrl('catalogsearch/result', array('_query' => array('q' => $this->escapeHtml($query))))
            )
        );
        $maxNumberSearchwordSuggestions = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_searchword_suggestions'));

        $titles = array($title);
        foreach ($collection as $item) {

            if ($counter >= $maxNumberSearchwordSuggestions) {
                break;
            }

            $title = mb_strtolower(trim($this->escapeHtml($item->getQueryText())));
            if (in_array($title, $titles)) {
                continue;
            }

            $titles[] = $title;
            $counter++;

            $_data = array(
                'title' => $title,
                'row_class' => $counter % 2 ? 'odd' : 'even',
                'num_of_results' => $item->getNumResults(),
                'url' => Mage::getUrl('catalogsearch/result', array('_query' => array('q' => $this->escapeHtml($item->getQueryText()))))
            );

            if ($counter == 1) {
                $_data['row_class'] .= ' first';
            }

            if ($item->getQueryText() == $query) {
                array_unshift($data, $_data);
            }
            else {
                $data[] = $_data;
            }
        }

        if (sizeof($data)) {
            $data[max(array_keys($data))]['row_class'] .= ' last';
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getProductSuggestions()
    {
        $products = Mage::getSingleton('integernet_solr/result')->getSolrResult()->response->docs;

        return $products;
    }

    public function getCategorySuggestions()
    {
        $categoryIds = (array)Mage::getSingleton('integernet_solr/result')->getSolrResult()->facet_counts->facet_fields->category;

        $categories = Mage::getResourceModel('catalog/category_collection')
            ->addAttributeToSelect(array('name', 'url_key'))
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('include_in_menu', 1)
            ->addAttributeToFilter('entity_id', array('in' => array_keys($categoryIds)));

        $categorySuggestions = array();
        $maxNumberCategories = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_category_suggestions'));
        $counter = 0;
        foreach($categoryIds as $categoryId => $numResults) {
            if ($category = $categories->getItemById($categoryId)) {
                if (++$counter > $maxNumberCategories) {
                    break;
                }

                $categorySuggestions[] = array(
                    'title' => $this->escapeHtml($this->_getCategoryTitle($category)),
                    'row_class' => '',
                    'num_of_results' => $numResults,
                    'url' => $this->_getCategoryUrl($category),
                );

            }
        }

        return $categorySuggestions;
    }

    /**
     * @return array
     */
    public function getAttributeSuggestions()
    {
        $attributesConfig = @unserialize(Mage::getStoreConfig('integernet_solr/autosuggest/attribute_filter_suggestions'));

        if (!$attributesConfig) {
            return array();
        }

        $attributesConfig = $this->_getSortedAttributesConfig($attributesConfig);
        $attributeSuggestions = array();

        foreach($attributesConfig as $attributeConfig) {

            $attributeCode = $attributeConfig['attribute_code'];
            $optionIds = (array)Mage::getSingleton('integernet_solr/result')->getSolrResult()->facet_counts->facet_fields->{$attributeCode . '_facet'};

            $maxNumberAttributeValues = intval($attributeConfig['max_number_suggestions']);
            $counter = 0;
            foreach($optionIds as $optionId => $numResults) {
                $attributeSuggestions[$attributeCode][] = array(
                    'title' => $this->getAttribute($attributeCode)->getSource()->getOptionText($optionId),
                    'row_class' => '',
                    'option_id' => $optionId,
                    'num_of_results' => $numResults,
                    'url' => Mage::getUrl('catalogsearch/result', array('_query' => array('q' => $this->escapeHtml($this->getQuery()), $attributeCode => $optionId))),
                );

                if (++$counter >= $maxNumberAttributeValues && $maxNumberAttributeValues > 0) {
                    break;
                }
            }
        }

        return $attributeSuggestions;
    }

    /**
     * @param string $attributeCode
     * @return Mage_Catalog_Model_Entity_Attribute
     */
    public function getAttribute($attributeCode)
    {
        if (!isset($this->_attributes[$attributeCode])) {
            $this->_attributes[$attributeCode] = Mage::getModel('catalog/product')->getResource()->getAttribute($attributeCode);
        }

        return $this->_attributes[$attributeCode];
    }

    /**
     * @param string $resultText
     * @param string $query
     * @return string
     */
    public function highlight($resultText, $query)
    {
        if (strpos($resultText, '<') === false) {
            return preg_replace('/(' . trim($query) . ')/i', '<span class="highlight">$1</span>', $resultText);
        }
        return preg_replace_callback('/(' . trim($query) . ')(.*?>)/i',
            array($this, '_checkOpenTag'),
            $resultText);
    }

    /**
     * @param array $matches
     * @return string
     */
    protected function _checkOpenTag($matches) {
        if (strpos($matches[0], '<') === false) {
            return $matches[0];
        } else {
            return '<span class="highlight">'.$matches[1].'</span>'.$matches[2];
        }
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->escapeHtml($this->helper('catalogsearch')->getQueryText());
    }

    /**
     * @param array $attributesConfig
     * @return array
     */
    protected function _getSortedAttributesConfig($attributesConfig)
    {
        $newAttributesConfig = array();
        foreach ($attributesConfig as $attributeConfig) {
            $sorting = intval($attributeConfig['sorting']);
            $newAttributesConfig[$sorting][] = $attributeConfig;
        }
        ksort($newAttributesConfig);

        $attributesConfig = array();
        foreach ($newAttributesConfig as $configs) {
            foreach ($configs as $config) {
                $attributesConfig[] = $config;
            }
        }
        return $attributesConfig;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    protected function _getCategoryUrl($category)
    {
        $linkType = Mage::getStoreConfig('integernet_solr/autosuggest/category_link_type');
        if ($linkType == IntegerNet_Solr_Model_Source_CategoryLinkType::CATEGORY_LINK_TYPE_FILTER) {
            return Mage::getUrl('catalogsearch/result', array(
                '_query' => array(
                    'q' => $this->escapeHtml($this->getQuery()),
                    'cat' => $category->getId()
                )
            ));
        }

        return $category->getUrl();
    }

    /**
     * Return category name or complete path, depending on what is configured
     * 
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    protected function _getCategoryTitle($category)
    {
        if (Mage::getStoreConfigFlag('integernet_solr/autosuggest/show_complete_category_path')) {
            $categoryPathIds = $category->getPathIds();
            array_shift($categoryPathIds);
            array_shift($categoryPathIds);
            array_pop($categoryPathIds);
            
            $categoryPathNames = array();
            foreach($categoryPathIds as $categoryId) {
                $categoryPathNames[] = Mage::getResourceSingleton('catalog/category')->getAttributeRawValue($categoryId, 'name', Mage::app()->getStore()->getId());
            }
            $categoryPathNames[] = $category->getName();
            return implode(' > ', $categoryPathNames);
        }
        return $category->getName();
    }
}