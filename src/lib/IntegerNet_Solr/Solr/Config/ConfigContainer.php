<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Config;

use IntegerNet\Solr\Implementor\SerializableConfig;

/**
 * Dumb, serializable Config implementation, used for caching
 */
final class ConfigContainer implements SerializableConfig
{
    /**
     * @var StoreConfig
     */
    private $storeConfig;
    /**
     * @var GeneralConfig
     */
    private $generalConfig;
    /**
     * @var ServerConfig
     */
    private $serverConfig;
    /**
     * @var IndexingConfig
     */
    private $indexingConfig;
    /**
     * @var AutosuggestConfig
     */
    private $autosuggestConfig;
    /**
     * @var FuzzyConfig
     */
    private $fuzzySearchConfig;
    /**
     * @var FuzzyConfig
     */
    private $fuzzyAutosuggestConfig;
    /**
     * @var ResultsConfig
     */
    private $resultsConfig;

    /**
     * @param StoreConfig $storeConfig
     * @param GeneralConfig $generalConfig
     * @param ServerConfig $serverConfig
     * @param IndexingConfig $indexingConfig
     * @param AutosuggestConfig $autosuggestConfig
     * @param FuzzyConfig $fuzzySearchConfig
     * @param FuzzyConfig $fuzzyAutosuggestConfig
     * @param ResultsConfig $resultsConfig
     */
    public function __construct(StoreConfig $storeConfig, GeneralConfig $generalConfig, ServerConfig $serverConfig, IndexingConfig $indexingConfig, AutosuggestConfig $autosuggestConfig, FuzzyConfig $fuzzySearchConfig, FuzzyConfig $fuzzyAutosuggestConfig, ResultsConfig $resultsConfig)
    {
        $this->storeConfig = $storeConfig;
        $this->generalConfig = $generalConfig;
        $this->serverConfig = $serverConfig;
        $this->indexingConfig = $indexingConfig;
        $this->autosuggestConfig = $autosuggestConfig;
        $this->fuzzySearchConfig = $fuzzySearchConfig;
        $this->fuzzyAutosuggestConfig = $fuzzyAutosuggestConfig;
        $this->resultsConfig = $resultsConfig;
    }

    /**
     * Returns required module independent store configuration
     *
     * @return StoreConfig
     */
    public function getStoreConfig()
    {
        return $this->storeConfig;
    }

    /**
     * Returns general Solr module configuration
     *
     * @return \IntegerNet\Solr\Config\GeneralConfig
     */
    public function getGeneralConfig()
    {
        return $this->generalConfig;
    }

    /**
     * Returns Solr server configuration
     *
     * @return \IntegerNet\Solr\Config\ServerConfig
     */
    public function getServerConfig()
    {
        return $this->serverConfig;
    }

    /**
     * Returns indexing configuration
     *
     * @return IndexingConfig
     */
    public function getIndexingConfig()
    {
        return $this->indexingConfig;
    }

    /**
     * Returns autosuggest configuration
     *
     * @return \IntegerNet\Solr\Config\AutosuggestConfig
     */
    public function getAutosuggestConfig()
    {
        return $this->autosuggestConfig;
    }

    /**
     * Returns fuzzy configuration for search
     *
     * @return FuzzyConfig
     */
    public function getFuzzySearchConfig()
    {
        return $this->fuzzySearchConfig;
    }

    /**
     * Returns fuzzy configuration for autosuggest
     *
     * @return FuzzyConfig
     */
    public function getFuzzyAutosuggestConfig()
    {
        return $this->fuzzyAutosuggestConfig;
    }

    /**
     * Returns search results configuration
     *
     * @return ResultsConfig
     */
    public function getResultsConfig()
    {
        return $this->resultsConfig;
    }

    /**
     * @return SerializableConfig
     */
    public function toSerializableConfig()
    {
        return $this;
    }


}