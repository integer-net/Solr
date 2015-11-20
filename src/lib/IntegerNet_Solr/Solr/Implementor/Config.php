<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\Solr\Implementor;
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\IndexingConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Config\ServerConfig;

/**
 * Interface for configuration reader. One instance per store.
 */
interface Config
{
    /**
     * Returns general Solr module configuration
     *
     * @return \IntegerNet\Solr\Config\GeneralConfig
     */
    public function getGeneralConfig();

    /**
     * Returns Solr server configuration
     *
     * @return \IntegerNet\Solr\Config\ServerConfig
     */
    public function getServerConfig();

    /**
     * Returns indexing configuration
     *
     * @return IndexingConfig
     */
    public function getIndexingConfig();

    /**
     * Returns autosuggest configuration
     *
     * @return \IntegerNet\Solr\Config\AutosuggestConfig
     */
    public function getAutosuggestConfig();

    /**
     * Returns fuzzy configuration for search
     *
     * @return FuzzyConfig
     */
    public function getFuzzySearchConfig();

    /**
     * Returns fuzzy configuration for autosuggest
     *
     * @return FuzzyConfig
     */
    public function getFuzzyAutosuggestConfig();

    /**
     * Returns search results configuration
     *
     * @return ResultsConfig
     */
    public function getResultsConfig();
}