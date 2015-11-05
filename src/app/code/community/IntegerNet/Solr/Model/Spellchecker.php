<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Milan Hacker
 */
class IntegerNet_Solr_Model_Spellchecker
{
    /** @var null|IntegerNet_Solr_Model_Resource_Solr */
    protected $_resource = null;

    /** @var null|IntegerNet_Solr_Model_Resource_Solr_Service */
    protected $_solrSpellchecker = null;

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
    public function getSolrSpellchecker($storeId = null)
    {
        if ($this->isActive()) {
            if (is_null($this->_solrSpellchecker)) {
                if (is_null($storeId)) {
                    $storeId = Mage::app()->getStore()->getId();
                }

                $startTime = microtime(true);

                $this->_solrSpellchecker = $this->_getResource()->search(
                    $storeId,
                    $this -> _getQueryText(),
                    0, // Start item
                    0, // Items per page
                    $this->_getParams($storeId)
                );

                if (Mage::getStoreConfigFlag('integernet_solr/general/log') || Mage::getStoreConfigFlag('integernet_solr/general/debug')) {
                    $this->_logSuggestion($this->_solrSpellchecker, microtime(true) - $startTime);
                }
            }
        }

        return $this->_solrSpellchecker;
    }

    /**
     * @return bool
     */
    public function isActive () {
        return Mage::getStoreConfigFlag('integernet_solr/results/spellchecker_active');
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function _getParams($storeId)
    {
        $params = array(
            'fq' => 'store_id:' . $storeId,
            'spellcheck' => 'true',
            'spellcheck.collate' => 'true',
            'spellcheck.count' => (int) Mage::getStoreConfig('integernet_solr/results/spellchecker_count'),
            'spellcheck.extendedResults' => 'true',
            'spellcheck.onlyMorePopular' => 'true',
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
        Mage::log($result, null, 'solr_spellchecker.log');
        Mage::log('Elapsed time: ' . $time . 's', null, 'solr_spellchecker.log');
    }
}