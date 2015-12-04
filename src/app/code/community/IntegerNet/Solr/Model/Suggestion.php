<?php
use IntegerNet\Solr\Resource\ResourceFacade;

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
    /** @var null|ResourceFacade */
    protected $_resource = null;

    /** @var null|Apache_Solr_Response */
    protected $_solrSuggestion = null;

    /**
     * @return ResourceFacade
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::helper('integernet_solr/factory')->getSolrResource();
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
            
            $startTime = microtime(true);
            
            $this->_solrSuggestion = $this->_getResource()->search(
                $storeId,
                '*',
                0, // Start item
                0, // Items per page
                $this->_getParams($storeId)
            );

            if (Mage::getStoreConfigFlag('integernet_solr/general/log')) {
                $this->_logSuggestion($this->_solrSuggestion, microtime(true) - $startTime);
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
            'fq' => 'store_id:' . $storeId,
            'df' => 'text_autocomplete',
            'facet' => 'true',
            'facet.field' => 'text_autocomplete',
            'facet.sort' => 'count',
            'facet.limit' => intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_searchword_suggestions')),
            'f.text_autocomplete.facet.prefix' => strtolower($this->_getQueryText()),
        );

        return $params;
    }

    /**
     * @return string
     */
    protected function _getQueryText()
    {
        $queryText = Mage::helper('catalogsearch')->getQuery()->getQueryText();
        $queryText = Mage::helper('integernet_solr/query')->escape($queryText);
        return $queryText;
    }

    protected function _logSuggestion($result, $time)
    {
        if (isset($result->response->docs)) {}
        Mage::log($result, null, 'solr_suggestions.log');
        Mage::log('Elapsed time: ' . $time . 's', null, 'solr_suggestions.log');
    }
}