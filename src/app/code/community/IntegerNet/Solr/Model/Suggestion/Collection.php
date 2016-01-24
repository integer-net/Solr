<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Suggestion_Collection extends Varien_Data_Collection
{

    /**
     * Collection constructor
     *
     * @param Mage_Core_Model_Resource_Abstract $resource
     */
    public function __construct($resource = null)
    {}

    /**
     * @deprecated was not used in the module anyway
     * @param int $storeId
     * @return IntegerNet_Solr_Model_Suggestion_Collection
     */
    public function setStoreId($storeId)
    {
        Mage::log(__METHOD__ . ' not supported.', Zend_Log::WARN);
        return $this;
    }

    /**
     * @deprecated was not used in the module anyway
     * @param string $queryText
     * @return IntegerNet_Solr_Model_Suggestion_Collection
     */
    public function setQueryFilter($queryText)
    {
        Mage::log(__METHOD__ . ' not supported.', Zend_Log::WARN);
        return $this;
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return  IntegerNet_Solr_Model_Suggestion_Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (!isset($this->_getSolrSuggestion()->facet_counts->facet_fields->text_autocomplete)
            && !isset($this->_getSolrSuggestion()->spellcheck->suggestions)) {
            return $this;
        }

        // Facet Search
        if (isset($this->_getSolrSuggestion()->facet_counts->facet_fields->text_autocomplete)) {

            $suggestions = (array)$this->_getSolrSuggestion()->facet_counts->facet_fields->text_autocomplete;

            foreach ($suggestions as $suggestion => $numResults) {
                $this->_items[] = new Varien_Object(array(
                    'query_text' => $suggestion,
                    'num_of_results' => $numResults,
                ));
            }


        // Spellchecker Search
        } else if (isset($this->_getSolrSuggestion()->spellcheck->suggestions)) {

            $spellchecker = (array)$this->_getSolrSuggestion()->spellcheck->suggestions;
            $queryText = Mage::helper('catalogsearch')->getQuery()->getQueryText();

            foreach ($spellchecker AS $word => $query) {
                $suggestions = (array) $query -> suggestion;

                foreach ($suggestions as $suggestion) {
                    $this->_items[] = new Varien_Object(array(
                        'query_text' => str_replace($word, $suggestion -> word, $queryText),
                        'num_of_results' => $suggestion -> freq,
                    ));
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve collection all items count
     *
     * @return int
     */
    public function getSize()
    {
        $this->load();
        return sizeof($this->_items);
    }

    /**
     * @return stdClass
     */
    protected function _getSolrSuggestion()
    {
        return Mage::getSingleton('integernet_solr/suggestion')->getSolrSuggestion();
    }
}