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
use IntegerNet\SolrSuggest\Plain\Block\Autosuggest as AutosuggestBlock;
use IntegerNet\SolrSuggest\Plain\Bridge\AttributeRepository;
use IntegerNet\SolrSuggest\Plain\Bridge\Logger;
use IntegerNet\SolrSuggest\Plain\Bridge\NullEventDispatcher;
use IntegerNet\SolrSuggest\Plain\Bridge\SearchUrl;
use IntegerNet\SolrSuggest\Plain\Bridge\TemplateRepository;
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
     * @var CacheReader
     */
    private $loadedCacheReader;

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
        $storeId = $this->request->getStoreId();
        $storeConfig = $this->getStoreConfig($storeId);
        if ($storeConfig->getGeneralConfig()->isLog()) {
            $logger = new Logger();
            $logger->setFile(
                $requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST ? 'solr_suggest.log' : 'solr.log'
            );
        } else {
            $logger = new \Psr\Log\NullLogger();
        }
        $applicationContext = new \IntegerNet\Solr\Request\ApplicationContext(
            new AttributeRepository($this->getLoadedCacheReader($storeId)), $storeConfig->getResultsConfig(), $storeConfig->getAutosuggestConfig(), new NullEventDispatcher(), $logger
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
        $storeId = $this->request->getStoreId();
        $storeConfig = $this->getStoreConfig($storeId);
        return new AutosuggestResult(
            $this->request->getStoreId(),
            $storeConfig->getGeneralConfig(),
            $storeConfig->getAutosuggestConfig(),
            $this->request,
            new SearchUrl($storeConfig->getStoreConfig()),
            new CategoryRepository($this->getLoadedCacheReader($storeId)),
            new AttributeRepository($this->getLoadedCacheReader($storeId)),
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
        $storeConfig = $this->getLoadedCacheReader($storeId)->getConfig($storeId);
        return $storeConfig;
    }

    /**
     * @return CacheWriter
     */
    public function getCacheWriter()
    {
        //TODO instantiate Magento to write cache on the fly
    }

    /**
     * @return CacheReader
     */
    public function getCacheReader()
    {
        return new CacheReader($this->cacheStorage);
    }


    public function getLoadedCacheReader($storeId)
    {
        if ($this->loadedCacheReader === null) {
            $this->loadedCacheReader = $this->getCacheReader();
        }
        $this->loadedCacheReader->load($storeId);
        return $this->loadedCacheReader;
    }

    public function getAutosuggestBlock()
    {
        $storeId = $this->request->getStoreId();
        $highlighter = new HtmlStringHighlighter();
        $templateRepository = new TemplateRepository($this->getLoadedCacheReader($storeId));
        return new AutosuggestBlock($storeId, $this, $templateRepository, $highlighter);
    }
}