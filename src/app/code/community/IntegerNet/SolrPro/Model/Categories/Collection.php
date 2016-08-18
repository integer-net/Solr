<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrPro
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Model_Categories_Collection extends Varien_Data_Collection
{
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
     * @return  Varien_Data_Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (Mage::getStoreConfigFlag('integernet_solr/category/use_in_search_results')) {
            $this->_items = $this->_getSolrResult()->response->docs;
        }

        return $this;
    }

    public function getColumnValues($colName)
    {
        $this->load();

        $col = array();
        foreach ($this->getItems() as $item) {
            $field = $item->getField($colName);
            $col[] = $field['value'];
        }
        return $col;

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
            $this->_totalRecords = 0;
            if (Mage::getStoreConfigFlag('integernet_solr/category/use_in_search_results')) {
                $maxNumberResults = Mage::getStoreConfig('integernet_solr/category/max_number_results');
                if ($maxNumberResults) {
                    $this->_totalRecords = min($maxNumberResults, $this->_getSolrResult()->response->numFound);
                } else {
                    $this->_totalRecords = $this->_getSolrResult()->response->numFound;
                }

            }
        }
        return intval($this->_totalRecords);
    }

    /**
     * @return \IntegerNet\Solr\Resource\SolrResponse
     */
    protected function _getSolrResult()
    {
        return Mage::getSingleton('integernet_solrpro/categories_result')->getSolrResult();
    }

    public function getLoadedIds()
    {}
}