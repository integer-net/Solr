<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Result_Collection extends Varien_Data_Collection
{
    /** @var null|IntegerNet_Solr_Model_Resource_Solr */
    protected $_resource = null;

    /**
     * Collection constructor
     *
     * @param Mage_Core_Model_Resource_Abstract $resource
     */
    public function __construct($resource = null)
    {}

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
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return  Varien_Data_Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        $storeId = Mage::app()->getStore()->getId();
        /** @var Apache_Solr_Response $result */
        $result = $this->_getSolrResult($storeId);
        $this->_items = $result->response->docs;
        return $this;
    }

    /**
     * @param $storeId
     * @return Apache_Solr_Response
     */
    protected function _getSolrResult($storeId)
    {
        $query = Mage::helper('catalogsearch')->getQuery()->getQueryText();
        $params = array('fq' => 'store_id:' . $storeId);
        return $this->_getResource()->search($storeId, $query, 0, 3, $params);
    }
}