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
    protected $_result = null;
    protected $_customHelper;

    protected function _construct()
    {
        $this->setTemplate('integernet/solr/autosuggest.phtml');
    }

    /**
     * @return IntegerNet_Solr_Autosuggest_Result
     */
    protected function _getResult()
    {
        if (is_null($this->_result)) {
            $this->_result = new IntegerNet_Solr_Autosuggest_Result();
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
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active')) {
            return parent::_toHtml();
        }

        $html = '';

        if (!$this->_beforeToHtml()) {
            return $html;
        }

        return $this->_getResult()->toHtml();
    }

    /**
     * @return IntegerNet_Solr_Autosuggest_Custom
     */
    public function getCustomHelper()
    {
        if (is_null($this->_customHelper)) {
            $this->_customHelper = new IntegerNet_Solr_Autosuggest_Custom();
        }
        return $this->_customHelper;
    }
}