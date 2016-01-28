<?php
use IntegerNet\SolrSuggest\Result\AutosuggestResult;

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
    protected $_result = null;
    protected $_customHelper;
    protected $_suggestData;

    protected function _construct()
    {
        $this->setTemplate('integernet/solr/autosuggest.phtml');
    }

    /**
     * @return AutosuggestResult
     */
    protected function _getResult()
    {
        if (is_null($this->_result)) {
            $this->_result = Mage::helper('integernet_solr/factory')->getAutosuggestResult();
        }

        return $this->_result;
    }

    /**
     * @return array
     */
    public function getSearchwordSuggestions()
    {
        return $this->_getResult()->getSearchwordSuggestions();
    }

    /**
     * @return array
     */
    public function getProductSuggestions()
    {
        return $this->_getResult()->getProductSuggestions();
    }

    /**
     * @return array
     */
    public function getCategorySuggestions()
    {
        return $this->_getResult()->getCategorySuggestions();
    }

    /**
     * @return array
     */
    public function getAttributeSuggestions()
    {
        return $this->_getResult()->getAttributeSuggestions();
    }

    /**
     * @param string $attributeCode
     * @return Mage_Catalog_Model_Entity_Attribute
     */
    public function getAttribute($attributeCode)
    {
        return $this->_getResult()->getAttribute($attributeCode);
    }

    /**
     * @param string $resultText
     * @param string $query
     * @return string
     */
    public function highlight($resultText, $query)
    {
        return $this->_getResult()->highlight($resultText, $query);
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->_getResult()->getQuery();
    }

    /**
     * Fallback if solr is deactivated
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('integernet_solr')->isActive()) {

            return $this->_getFallbackHtml();
        }

        return parent::_toHtml();
    }

    public function getSuggestData()
    {
        if (!$this->_suggestData) {
            $collection = Mage::helper('catalogsearch')->getSuggestCollection();
            $query = Mage::helper('catalogsearch')->getQueryText();
            $counter = 0;
            $data = array();
            foreach ($collection as $item) {
                $_data = array(
                    'title' => $item->getQueryText(),
                    'row_class' => (++$counter)%2?'odd':'even',
                    'num_of_results' => $item->getNumResults()
                );

                if ($item->getQueryText() == $query) {
                    array_unshift($data, $_data);
                }
                else {
                    $data[] = $_data;
                }
            }
            $this->_suggestData = $data;
        }
        return $this->_suggestData;
    }

    /**
     * @return string
     */
    protected function _getFallbackHtml()
    {
        $html = '';

        if (!$this->_beforeToHtml()) {
            return $html;
        }

        $suggestData = $this->getSuggestData();
        if (!($count = count($suggestData))) {
            return $html;
        }

        $count--;

        $html = '<ul><li style="display:none"></li>';
        foreach ($suggestData as $index => $item) {
            if ($index == 0) {
                $item['row_class'] .= ' first';
            }

            if ($index == $count) {
                $item['row_class'] .= ' last';
            }

            $html .= '<li title="' . $this->escapeHtml($item['title']) . '" class="' . $item['row_class'] . '">'
                . '<span class="amount">' . $item['num_of_results'] . '</span>' . $this->escapeHtml($item['title']) . '</li>';
        }

        $html .= '</ul>';

        return $html;
    }
}