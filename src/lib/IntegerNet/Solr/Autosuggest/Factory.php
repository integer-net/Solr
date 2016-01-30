<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Factory;
use IntegerNet\SolrSuggest\Implementor\Factory as SuggestFactory;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use IntegerNet\SolrSuggest\Request\AutosuggestRequestFactory;
use IntegerNet\Solr\Request\SearchRequestFactory;
use IntegerNet\SolrSuggest\Request\SearchTermSuggestRequestFactory;
use IntegerNet\SolrSuggest\Util\HtmlStringHighlighter;

/**
 * This class is a low weight replacement for the factory helper class in autosuggest calls
 */
final class IntegerNet_Solr_Autosuggest_Factory implements Factory, SuggestFactory
{
    /**
     * Returns new configured Solr recource
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $store = IntegerNet_Solr_Autosuggest_Mage::app()->getStore();
        $storeConfig = array(
            $store->getId() => new IntegerNet_Solr_Model_Config_Store($store->getId())
        );

        return new ResourceFacade($storeConfig);
    }

    /**
     * Returns new Solr result wrapper
     *
     * @return \IntegerNet\Solr\Request\Request
     */
    public function getSolrRequest($requestMode = self::REQUEST_MODE_AUTODETECT)
    {
        $store = IntegerNet_Solr_Autosuggest_Mage::app()->getStore();
        $storeConfig = $this->getStoreConfig($store->getId());
        $helper = IntegerNet_Solr_Autosuggest_Mage::helper('integernet_solr');
        if ($storeConfig->getGeneralConfig()->isLog()) {
            $logger = Mage::helper('integernet_solr/log');
            if ($logger instanceof IntegerNet_Solr_Helper_Log) {
                $logger->setFile(
                    $requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST ? 'solr_suggest.log' : 'solr.log'
                );
            }
        } else {
            $logger = new \Psr\Log\NullLogger();
        }
        $applicationContext = new \IntegerNet\Solr\Request\ApplicationContext(
            $helper, $storeConfig->getResultsConfig(), $storeConfig->getAutosuggestConfig(), $helper, $logger
        );
        switch ($requestMode) {
            case self::REQUEST_MODE_SEARCHTERM_SUGGEST:
                $applicationContext->setQuery(Mage::helper('integernet_solr/searchterm'));
                $factory = new SearchTermSuggestRequestFactory($applicationContext, $this->getSolrResource(), $store->getId());
                break;
            default:
            case self::REQUEST_MODE_AUTOSUGGEST:
                $applicationContext
                    ->setFuzzyConfig($storeConfig->getFuzzyAutosuggestConfig())
                    ->setQuery(Mage::helper('integernet_solr'));
                $factory = new AutosuggestRequestFactory($applicationContext, $this->getSolrResource(), $store->getId());
        }
        return $factory->createRequest();
    }

    /**
     * @return \IntegerNet\SolrSuggest\Result\AutosuggestResult
     */
    public function getAutosuggestResult()
    {
        $store = IntegerNet_Solr_Autosuggest_Mage::app()->getStore();
        $storeConfig = $this->getStoreConfig($store->getId());
        $helper = IntegerNet_Solr_Autosuggest_Mage::helper('integernet_solr');
        return new AutosuggestResult(
            $store->getId(),
            $storeConfig->getGeneralConfig(),
            $storeConfig->getAutosuggestConfig(),
            $helper,
            $helper,
            new IntegerNet_Solr_Autosuggest_CategoryRepository(),
            $helper,
            $this->getSolrRequest(self::REQUEST_MODE_AUTOSUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_SEARCHTERM_SUGGEST),
            new HtmlStringHighlighter()
        );
    }

    /**
     * @param int $storeId
     * @return IntegerNet_Solr_Model_Config_Store
     */
    public function getStoreConfig($storeId)
    {
        $storeConfig = new IntegerNet_Solr_Model_Config_Store($storeId);
        return $storeConfig;
    }


}