<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain;

use IntegerNet\Solr\Implementor\Factory as FactoryInterface;
use IntegerNet\SolrSuggest\Implementor\Factory as SuggestFactoryInterface;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrSuggest\Plain\Bridge\AttributeRepository;
use IntegerNet\SolrSuggest\Plain\Bridge\Logger;
use IntegerNet\SolrSuggest\Plain\Bridge\NullEventDispatcher;
use IntegerNet\SolrSuggest\Plain\Bridge\SearchUrl;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;
use IntegerNet\SolrSuggest\Plain\Cache\CacheStorage;
use IntegerNet\SolrSuggest\Plain\Cache\CacheWriter;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use IntegerNet\SolrSuggest\Request\AutosuggestRequestFactory;
use IntegerNet\SolrSuggest\Request\SearchTermSuggestRequestFactory;
use IntegerNet\SolrSuggest\Util\HtmlStringHighlighter;
use IntegerNet\SolrSuggest\Plain\Bridge\CategoryRepository;
use IntegerNet_Solr_Model_Config_Store;

final class Factory implements FactoryInterface, SuggestFactoryInterface
{
    /**
     * @var AutosuggestRequest
     */
    private $request;
    /**
     * @var CacheStorage
     */
    private $cacheStorage;

    /**
     * @param AutosuggestRequest $request
     * @param CacheStorage $cacheStorage
     */
    public function __construct(AutosuggestRequest $request, CacheStorage $cacheStorage)
    {
        $this->request = $request;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * Returns new configured Solr recource
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $storeConfig = array(
            $this->request->getStoreId() => new IntegerNet_Solr_Model_Config_Store($this->request->getStoreId())
        );

        return new ResourceFacade($storeConfig);
    }

    /**
     * Returns new Solr result wrapper
     *
     * @param int $requestMode
     * @return \IntegerNet\Solr\Request\Request
     */
    public function getSolrRequest($requestMode = self::REQUEST_MODE_AUTODETECT)
    {
        $storeConfig = $this->getStoreConfig($this->request->getStoreId());
        if ($storeConfig->getGeneralConfig()->isLog()) {
            $logger = new Logger();
            $logger->setFile(
                $requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST ? 'solr_suggest.log' : 'solr.log'
            );
        } else {
            $logger = new \Psr\Log\NullLogger();
        }
        $applicationContext = new \IntegerNet\Solr\Request\ApplicationContext(
            new AttributeRepository($this->getCacheReader()), $storeConfig->getResultsConfig(), $storeConfig->getAutosuggestConfig(), new NullEventDispatcher(), $logger
        );
        switch ($requestMode) {
            case self::REQUEST_MODE_SEARCHTERM_SUGGEST:
                $applicationContext->setQuery($this->request);
                $factory = new SearchTermSuggestRequestFactory($applicationContext, $this->getSolrResource(), $this->request->getStoreId());
                break;
            default:
            case self::REQUEST_MODE_AUTOSUGGEST:
                $applicationContext
                    ->setFuzzyConfig($storeConfig->getFuzzyAutosuggestConfig())
                    ->setQuery($this->request);
                $factory = new AutosuggestRequestFactory($applicationContext, $this->getSolrResource(), $this->request->getStoreId());
        }
        return $factory->createRequest();
    }

    /**
     * @return \IntegerNet\SolrSuggest\Result\AutosuggestResult
     */
    public function getAutosuggestResult()
    {
        $storeConfig = $this->getStoreConfig($this->request->getStoreId());
        return new AutosuggestResult(
            $this->request->getStoreId(),
            $storeConfig->getGeneralConfig(),
            $storeConfig->getAutosuggestConfig(),
            $this->request,
            new SearchUrl(),
            new CategoryRepository(),
            new AttributeRepository($this->getCacheReader()),
            $this->getSolrRequest(self::REQUEST_MODE_AUTOSUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_SEARCHTERM_SUGGEST)
        );
    }

    /**
     * @param $storeId
     * @return \IntegerNet\Solr\Implementor\Config
     */
    public function getStoreConfig($storeId)
    {
        //$storeConfig = new IntegerNet_Solr_Model_Config_Store($storeId);
        $storeConfig = $this->getCacheReader()->getConfig($storeId);
        return $storeConfig;
    }

    /**
     * @return CacheWriter
     */
    public function getCacheWriter()
    {
        //TODO instantiate Magento to write cache on the fly
    }


    public function getCacheReader()
    {
        return new CacheReader($this->cacheStorage);
    }

}