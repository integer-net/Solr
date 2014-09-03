<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Block_Autocomplete extends Mage_Core_Block_Template
{
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
        $data = array(
            array(
                'title' => $this->escapeHtml($query),
                'row_class' => 'odd',
                'num_of_results' => '',
                'url' => Mage::getUrl('catalogsearch/result', array('q' => $this->escapeHtml($query)))
            )
        );
        $maxNumberSearchwordSuggestions = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_searchword_suggestions'));
        
        foreach ($collection as $item) {

            if (++$counter > $maxNumberSearchwordSuggestions) {
                break;
            }
            
            $_data = array(
                'title' => $this->escapeHtml($item->getQueryText()),
                'row_class' => $counter % 2 ? 'odd' : 'even',
                'num_of_results' => $item->getNumResults(),
                'url' => Mage::getUrl('catalogsearch/result', array('q' => $this->escapeHtml($item->getQueryText())))
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
                $categorySuggestions[] = array(
                    'title' => $this->escapeHtml($category->getName()),
                    'row_class' => '',
                    'num_of_results' => $numResults,
                    'url' => Mage::getUrl('catalogsearch/result', array('q' => $this->escapeHtml($this->getQuery()), 'cat' => $categoryId)),
                );

                if (++$counter >= $maxNumberCategories) {
                    break;
                }
            }
        }

        return $categorySuggestions;
    }

    /**
     * @param string $resultText
     * @param string $query
     * @return string
     */
    public function highlight($resultText, $query)
    {
        if (strpos($resultText, '<') === false) {
            return str_replace(trim($query), '<span class="highlight">'.trim($query).'</span>', $resultText);
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
        return $this->helper('catalogsearch')->getQueryText();
    }
}