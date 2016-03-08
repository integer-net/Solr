<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain;

use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\IndexingConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Config\ServerConfig;
use IntegerNet\Solr\Config\StoreConfig;
use IntegerNet\Solr\Config\CategoryConfig;
use IntegerNet\Solr\Config\CmsConfig;
use IntegerNet\Solr\Implementor\Config as ConfigInterface;
use IntegerNet\Solr\Implementor\SerializableConfig;

/**
 * Dumb, serializable Config implementation, used for caching
 */
final class Config implements SerializableConfig
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
     * @var CmsConfig
     */
    private $cmsConfig;
    /**
     * @var CategoryConfig
     */
    private $categoryConfig;

    /**
     * @param StoreConfig $storeConfig
     * @param GeneralConfig $generalConfig
     * @param ServerConfig $serverConfig
     * @param IndexingConfig $indexingConfig
     * @param AutosuggestConfig $autosuggestConfig
     * @param FuzzyConfig $fuzzySearchConfig
     * @param FuzzyConfig $fuzzyAutosuggestConfig
     * @param ResultsConfig $resultsConfig
     * @param CategoryConfig $categoryConfig
     * @param CmsConfig $cmsConfig
     */
    public function __construct(
        StoreConfig $storeConfig, 
        GeneralConfig $generalConfig, 
        ServerConfig $serverConfig, 
        IndexingConfig $indexingConfig, 
        AutosuggestConfig $autosuggestConfig, 
        FuzzyConfig $fuzzySearchConfig, 
        FuzzyConfig $fuzzyAutosuggestConfig, 
        ResultsConfig $resultsConfig, 
        CategoryConfig $categoryConfig,
        CmsConfig $cmsConfig
    )
    {
        $this->storeConfig = $storeConfig;
        $this->generalConfig = $generalConfig;
        $this->serverConfig = $serverConfig;
        $this->indexingConfig = $indexingConfig;
        $this->autosuggestConfig = $autosuggestConfig;
        $this->fuzzySearchConfig = $fuzzySearchConfig;
        $this->fuzzyAutosuggestConfig = $fuzzyAutosuggestConfig;
        $this->resultsConfig = $resultsConfig;
        $this->categoryConfig = $categoryConfig;
        $this->cmsConfig = $cmsConfig;
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
     * Returns category configuration
     *
     * @return CategoryConfig
     */
    public function getCategoryConfig()
    {
        return $this->categoryConfig;
    }

    /**
     * Returns cms configuration
     *
     * @return CmsConfig
     */
    public function getCmsConfig()
    {
        return $this->cmsConfig;
    }

    public static function fromConfig(ConfigInterface $other)
    {
        return new static(
            $other->getStoreConfig(),
            $other->getGeneralConfig(),
            $other->getServerConfig(),
            $other->getIndexingConfig(),
            $other->getAutosuggestConfig(),
            $other->getFuzzySearchConfig(),
            $other->getFuzzyAutosuggestConfig(),
            $other->getResultsConfig(),
            $other->getCategoryConfig(),
            $other->getCmsConfig()
        );
    }

}