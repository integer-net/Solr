<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Resource_CatalogSearch_Query_Collection extends Varien_Data_Collection
{
    protected $_storeId = null;

    /**
     * Collection constructor
     *
     * @param Mage_Core_Model_Resource_Abstract $resource
     */
    public function __construct($resource = null)
    {}

    /**
     * @param int $storeId
     * @return IntegerNet_Solr_Model_Resource_CatalogSearch_Query_Collection
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * @param string $queryText
     * @return IntegerNet_Solr_Model_Resource_CatalogSearch_Query_Collection
     */
    public function setQueryFilter($queryText)
    {
        return $this;
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return  Varien_Data_Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        $suggestions = (array)$this->_getSolrSuggestion()->spellcheck->suggestions;

        foreach (current($suggestions)->suggestion as $suggestion) {
            $this->_items[] = new Varien_Object(array(
                'query_text' => $suggestion,
                'num_of_results' => '',
            ));
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
        if (is_null($this->_totalRecords)) {
            $this->_totalRecords = $this->_getSolrSuggestion()->spellcheck->suggestions->numFound;
        }
        return intval($this->_totalRecords);
    }

    /**
     * @return stdClass
     */
    protected function _getSolrSuggestion()
    {
        return Mage::getSingleton('integernet_solr/suggestion')->getSolrSuggestion($this->_storeId);
    }
}