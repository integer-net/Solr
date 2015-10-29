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
            
            $startTime = microtime(true);
            
            $this->_solrSuggestion = $this->_getResource()->search(
                $storeId,
                '*',
                0, // Start item
                0, // Items per page
                $this->_getParams($storeId)
            );

            if (Mage::getStoreConfigFlag('integernet_solr/general/log') || Mage::getStoreConfigFlag('integernet_solr/general/debug')) {
                $this->_logSuggestion($this->_solrSuggestion, microtime(true) - $startTime);
            }


            /**
             * Fallback if the User type something that can't be found, then ask the spellchecker
             */
            if (Mage::getStoreConfigFlag('integernet_solr/results/spellchecker_active')
                && count((array) $this->_solrSuggestion->facet_counts->facet_fields->text_autocomplete) == 0) {

                $startTime = microtime(true);
                $spellchecker = new IntegerNet_Solr_Model_Spellchecker();
                $this->_solrSuggestion = $spellchecker->getSolrSpellchecker();

                if (Mage::getStoreConfigFlag('integernet_solr/general/log') || Mage::getStoreConfigFlag('integernet_solr/general/debug')) {
                    $this->_logSuggestion($this->_solrSuggestion, microtime(true) - $startTime);
                }
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

        return $queryText;
    }

    protected function _logSuggestion($result, $time)
    {
        if (isset($result->response->docs)) {}
        Mage::log($result, null, 'solr_suggestions.log');
        Mage::log('Elapsed time: ' . $time . 's', null, 'solr_suggestions.log');
    }
}