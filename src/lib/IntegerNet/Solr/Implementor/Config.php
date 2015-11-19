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
 * Interface for configuration reader. One instance per store.
 */
interface IntegerNet_Solr_Implementor_Config
{
    /**
     * Returns general Solr module configuration
     *
     * @return IntegerNet_Solr_Config_General
     */
    public function getGeneralConfig();
    /**
     * Returns Solr server configuration
     *
     * @return IntegerNet_Solr_Config_Server
     */
    public function getServerConfig();

    /**
     * Returns indexing configuration
     *
     * @return IntegerNet_Solr_Config_Indexing
     */
    public function getIndexingConfig();

    /**
     * Returns autosuggest configuration
     *
     * @return IntegerNet_Solr_Config_Autosuggest
     */
    public function getAutosuggestConfig();

    /**
     * Returns fuzzy configuration for search
     *
     * @return IntegerNet_Solr_Config_Fuzzy
     */
    public function getFuzzySearchConfig();

    /**
     * Returns fuzzy configuration for autosuggest
     *
     * @return IntegerNet_Solr_Config_Fuzzy
     */
    public function getFuzzyAutosuggestConfig();

    /**
     * Returns search results configuration
     *
     * @return IntegerNet_Solr_Config_Results
     */
    public function getResultsConfig();
}