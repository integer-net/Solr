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
final class IntegerNet_Solr_Model_Config_Store implements IntegerNet_Solr_Implementor_Config
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
     * @var IntegerNet_Solr_Config_Autosuggest
     */
    protected $_autosuggest;
    /**
     * @var IntegerNet_Solr_Config_Fuzzy
     */
    protected $_fuzzySearch;
    /**
     * @var IntegerNet_Solr_Config_Fuzzy
     */
    protected $_fuzzyAutosuggest;
    /**
     * @var IntegerNet_Solr_Config_Results
     */
    protected $_results;

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
            $prefix = 'integernet_solr/general/';
            $this->_general = new IntegerNet_Solr_Config_General(
                $this->_getConfigFlag($prefix . 'is_active'),
                $this->_getConfig($prefix . 'license_key'),
                $this->_getConfigFlag($prefix . 'log'),
                $this->_getConfigFlag($prefix . 'debug')
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
                $this->_getConfig('integernet_solr/indexing/swap_core'),
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
                $this->_getConfigFlag($prefix . 'delete_documents_before_indexing'),
                $this->_getConfigFlag($prefix . 'swap_cores')
            );
        }
        return $this->_indexing;
    }

    /**
     * Returns autosuggest configuration
     *
     * @return IntegerNet_Solr_Config_Autosuggest
     */
    public function getAutosuggestConfig()
    {
        if ($this->_autosuggest === null) {
            $prefix = 'integernet_solr/autosuggest/';
            $this->_autosuggest = new IntegerNet_Solr_Config_Autosuggest(
                $this->_getConfigFlag($prefix . 'is_active'),
                $this->_getConfig($prefix . 'use_php_file_in_home_dir'),
                $this->_getConfig($prefix . 'max_number_searchword_suggestions'),
                $this->_getConfig($prefix . 'max_number_product_suggestions'),
                $this->_getConfig($prefix . 'max_number_category_suggestions'),
                $this->_getConfigFlag($prefix . 'show_complete_category_path'),
                $this->_getConfigFlag($prefix . 'category_link_type'),
                unserialize($this->_getConfig($prefix . 'attribute_filter_suggestions'))
            );
        }
        return $this->_autosuggest;
    }

    /**
     * Returns fuzzy configuration for search
     *
     * @return IntegerNet_Solr_Config_Fuzzy
     */
    public function getFuzzySearchConfig()
    {
        if ($this->_fuzzySearch === null) {
            $prefix = 'integernet_solr/fuzzy/';
            $this->_fuzzySearch = new IntegerNet_Solr_Config_Fuzzy(
                $this->_getConfigFlag($prefix . 'is_active'),
                $this->_getConfig($prefix . 'sensitivity'),
                $this->_getConfig($prefix . 'minimum_results')
            );
        }
        return $this->_fuzzySearch;
    }

    /**
     * Returns fuzzy configuration for autosuggest
     *
     * @return IntegerNet_Solr_Config_Fuzzy
     */
    public function getFuzzyAutosuggestConfig()
    {
        if ($this->_fuzzyAutosuggest === null) {
            $prefix = 'integernet_solr/fuzzy/';
            $this->_fuzzyAutosuggest = new IntegerNet_Solr_Config_Fuzzy(
                $this->_getConfigFlag($prefix . 'is_active_autosuggest'),
                $this->_getConfig($prefix . 'sensitivity_autosuggest'),
                $this->_getConfig($prefix . 'minimum_results_autosuggest')
            );
        }
        return $this->_fuzzyAutosuggest;
    }

    /**
     * Returns search results configuration
     *
     * @return IntegerNet_Solr_Config_Results
     */
    public function getResultsConfig()
    {
        if ($this->_results === null) {
            $prefix = 'integernet_solr/results/';
            $this->_results = new IntegerNet_Solr_Config_Results(
                $this->_getConfigFlag($prefix . 'use_html_from_solr'),
                $this->_getConfig($prefix . 'search_operator'),
                $this->_getConfig($prefix . 'price_step_size'),
                $this->_getConfig($prefix . 'max_price'),
                $this->_getConfigFlag($prefix . 'use_custom_price_intervals'),
                explode(',', $this->_getConfig($prefix . 'custom_price_intervals'))
            );
        }
        return $this->_results;
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