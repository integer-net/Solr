<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Suggestion
{
    const ITEMS_PER_PAGE = 5;

    /** @var null|IntegerNet_Solr_Model_Resource_Solr */
    protected $_resource = null;

    /** @var null|IntegerNet_Solr_Model_Resource_Solr_Service */
    protected $_solrSuggestion = null;

    /**
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::getResourceModel('integernet_solr/solr');
        }

        return $this->_resource;
    }

    /**
     * @param $storeId
     * @return Apache_Solr_Response
     */
    public function getSolrSuggestion($storeId = null)
    {
        if (is_null($this->_solrSuggestion)) {
            if (is_null($storeId)) {
                $storeId = Mage::app()->getStore()->getId();
            }
            $this->_solrSuggestion = $this->_getResource()->suggest(
                $storeId,
                $this->_getQueryText(),
                0, // Start item
                self::ITEMS_PER_PAGE, // Items per page
                $this->_getParams($storeId)
            );

            if (Mage::getStoreConfigFlag('integernet_solr/general/log')) {

                $this->_logSuggestion();
            }
        }

        return $this->_solrSuggestion;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function _getParams($storeId)
    {
        $params = array(
            'qf' => $this->_getSearchFieldCodes(),
        );

        return $params;
    }

    /**
     * @return array
     */
    protected function _getSearchFieldCodes()
    {
        $codes = array('category');
        foreach(Mage::helper('integernet_solr')->getSearchableAttributes() as $attribute) {
            $codes[] = Mage::helper('integernet_solr')->getFieldName($attribute);
        }
        return $codes;
    }

    /**
     * @return string
     */
    protected function _getQueryText()
    {
        $queryText = Mage::helper('catalogsearch')->getQuery()->getQueryText();

        return $queryText;
    }

    protected function _logSuggestion()
    {
        Mage::log($this->_solrSuggestion, null, 'solr_suggestions.log');
    }
}