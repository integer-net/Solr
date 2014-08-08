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
    
    /** @var null|Apache_Solr_Response */
    protected $_solrResult = null;
    
    /** @var null|Mage_Catalog_Block_Product_List_Toolbar */
    protected $_toolbarBlock = null;

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
        /** @var Apache_Solr_Response $result */
        $result = $this->_getSolrResult();
        $this->_items = $result->response->docs;
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
            $this->_totalRecords = $this->_getSolrResult()->response->numFound;
        }
        return intval($this->_totalRecords);
    }

    /**
     * @param $storeId
     * @return Apache_Solr_Response
     */
    protected function _getSolrResult($storeId = null)
    {
        if (is_null($this->_solrResult)) {
            if (is_null($storeId)) {
                $storeId = Mage::app()->getStore()->getId();
            }
            $this->_solrResult = $this->_getResource()->search(
                $storeId,
                $this->_getQueryText(), 
                $this->_getCurrentPage() * $this->_getPageSize(), // Start item
                $this->_getPageSize(), // Items per page
                $this->_getParams($storeId)
            );
        }
        
        return $this->_solrResult;
    }

    /**
     * @return int
     */
    protected function _getCurrentPage()
    {
        return $this->_getToolbarBlock()->getCurrentPage() - 1;
    }

    /**
     * @return int
     */
    protected function _getCurrentOrder()
    {
        return $this->_getToolbarBlock()->getCurrentOrder();
    }

    /**
     * @return int
     */
    protected function _getPageSize()
    {
        return $this->_getToolbarBlock()->getLimit();
    }

    /**
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    protected function _getToolbarBlock()
    {
        if (is_null($this->_toolbarBlock)) {
            $this->_toolbarBlock = Mage::app()->getLayout()->getBlock('product_list_toolbar');
        }
        return $this->_toolbarBlock;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function _getParams($storeId)
    {
        $params = array('fq' => 'store_id:' . $storeId);
        
        switch($this->_getCurrentOrder()) {
            case 'position':
                $params['sort'] = '';
                break;
            case 'price':
                $params['sort'] = 'price_f asc';
                break;
            default:
                $params['sort'] = $this->_getCurrentOrder() . '_t asc'; 
                
        }
        return $params;
    }

    /**
     * @return string
     */
    protected function _getQueryText()
    {
        return Mage::helper('catalogsearch')->getQuery()->getQueryText();
    }
}