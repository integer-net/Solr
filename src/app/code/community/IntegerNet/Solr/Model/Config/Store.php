<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * Magento configuration reader, one instance per store view
 */
final class IntegerNet_Solr_Model_Config_Store implements IntegerNet_Solr_Config_Interface
{
    /**
     * @var int
     */
    protected $_storeId;
    /**
     * @var IntegerNet_Solr_Config_General
     */
    protected $_general;
    /**
     * @var IntegerNet_Solr_Config_Server
     */
    protected $_server;
    /**
     * @var IntegerNet_Solr_Config_Indexing
     */
    protected $_indexing;

    /**
     * @param int $_storeId
     */
    public function __construct($_storeId)
    {
        $this->_storeId = $_storeId;
    }

    /**
     * Returns general Solr module configuration
     *
     * @return IntegerNet_Solr_Config_General
     */
    public function getGeneralConfig()
    {
        if ($this->_general === null) {
            $prefix = 'integernet_solr/general';
            $this->_general = new IntegerNet_Solr_Config_General(
                $this->_getConfig($prefix . 'is_active'),
                $this->_getConfig($prefix . 'license_key'),
                $this->_getConfig($prefix . 'log'),
                $this->_getConfig($prefix . 'debug')
            );
        }
        return $this->_general;
    }


    /**
     * Returns Solr server configuration
     *
     * @return IntegerNet_Solr_Config_Server
     */
    public function getServerConfig()
    {
        if ($this->_server === null) {
            $prefix = 'integernet_solr/server/';
            $this->_server = new IntegerNet_Solr_Config_Server(
                $this->_getConfig($prefix . 'host'),
                $this->_getConfig($prefix . 'port'),
                $this->_getConfig($prefix . 'path'),
                $this->_getConfig($prefix . 'core'),
                $this->_getConfigFlag($prefix . 'use_https'),
                $this->_getConfig($prefix . 'http_method'),
                $this->_getConfigFlag($prefix . 'use_http_basic_auth'),
                $this->_getConfig($prefix . 'http_basic_auth_username'),
                $this->_getConfig($prefix . 'http_basic_auth_password')
            );
        }
        return $this->_server;
    }

    /**
     * Returns indexing configuration
     *
     * @return IntegerNet_Solr_Config_Indexing
     */
    public function getIndexingConfig()
    {
        if ($this->_indexing === null) {
            $prefix = 'integernet_solr/indexing/';
            $this->_indexing = new IntegerNet_Solr_Config_Indexing(
                $this->_getConfig($prefix . 'pagesize'),
                $this->_getConfig($prefix . 'delete_documents_before_indexing'),
                $this->_getConfig($prefix . 'swap_cores'),
                $this->_getConfig($prefix . 'swap_core')
            );
        }
        return $this->_indexing;
    }
    
    protected function _getConfig($path)
    {
        return Mage::getStoreConfig($path, $this->_storeId);
    }

    protected function _getConfigFlag($path)
    {
        return Mage::getStoreConfigFlag($path, $this->_storeId);
    }

}