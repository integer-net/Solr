<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Milan Hacker
 */
class IntegerNet_Solr_Model_Spellchecker_Collection extends Varien_Data_Collection
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
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return IntegerNet_Solr_Model_Spellchecker_Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (count($this->_items) > 0) {
            return $this;
        }

        $queries = (array) $this->_getSolrSpellchecker()->spellcheck->suggestions;
        $queryText = Mage::helper('catalogsearch')->getQuery()->getQueryText();

        $this -> _items['query'] = new Varien_Object(array(
            'search' => $queryText
        ));

        $build = array();
        foreach ($queries AS $query) {
            foreach ((array) $query -> suggestion as $value) {
                $build[] = array(
                    'word' => $value -> word,
                    'freq' => (int) $value -> freq,
                );
            }
        }

        usort($build, function ($a, $b) {
            return ($a["freq"] < $b["freq"]);
        });

        foreach ($build AS $suggest) {
            $this->_items[] = new Varien_Object(array(
                'query_text' => $suggest['word'],
                'num_of_results' => $suggest['freq'],
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
        return sizeof($this->_items);
    }

    /**
     * @return stdClass
     */
    protected function _getSolrSpellchecker()
    {
        return Mage::getSingleton('integernet_solr/spellchecker')->getSolrSpellchecker($this->_storeId);
    }
}