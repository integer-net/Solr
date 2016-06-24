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

use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\StoreConfig;
use IntegerNet\Solr\Implementor\SolrRequestFactory;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrSuggest\Implementor\Factory\AppFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\AutosuggestResultFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\CacheReaderFactory;
use IntegerNet\SolrSuggest\Plain\Block\Autosuggest as AutosuggestBlock;
use IntegerNet\SolrSuggest\Plain\Bridge\AttributeRepository;
use IntegerNet\SolrSuggest\Plain\Bridge\CategoryRepository;
use IntegerNet\SolrSuggest\Plain\Bridge\NullEventDispatcher;
use IntegerNet\SolrSuggest\Plain\Bridge\SearchUrl;
use IntegerNet\SolrSuggest\Plain\Bridge\TemplateRepository;
use IntegerNet\SolrSuggest\Plain\Cache\CacheItemNotFoundException;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;
use IntegerNet\SolrSuggest\Plain\Cache\CacheStorage;
use IntegerNet\SolrSuggest\Plain\Factory\LoggerFactory;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;
use IntegerNet\SolrSuggest\Request\AutosuggestRequestFactory;
use IntegerNet\SolrSuggest\Request\SearchTermSuggestRequestFactory;
use IntegerNet\SolrCategories\Request\CategorySuggestRequestFactory;
use IntegerNet\SolrCms\Request\CmsPageSuggestRequestFactory;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use IntegerNet\SolrSuggest\Util\HtmlStringHighlighter;
use Psr\Log\LoggerInterface;

// not final to allow partial mocking in integration test
class Factory implements SolrRequestFactory, AutosuggestResultFactory, CacheReaderFactory
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
     * @var \Closure
     */
    private $loadApplicationCallback;

    /**
     * @param AutosuggestRequest $request
     * @param CacheStorage $cacheStorage
     * @param \Closure $loadApplicationCallback
     */
    public function __construct(AutosuggestRequest $request, CacheStorage $cacheStorage, \Closure $loadApplicationCallback)
    {
        $this->request = $request;
        $this->cacheStorage = $cacheStorage;
        $this->loadApplicationCallback = $loadApplicationCallback;
    }

    /**
     * Returns new configured Solr recource
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $storeId = $this->request->getStoreId();
        $storeConfig = array(
            $storeId => $this->getLoadedCacheReader($storeId)->getConfig($storeId)
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
        $storeConfig = $this->getStoreConfigByStoreId($storeId);
        $logFile = $requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST ? 'solr_suggest.log' : 'solr.log';
        $logger = $this->getLogger($storeConfig->getGeneralConfig(), $storeConfig->getStoreConfig(), $logFile);
        $applicationContext = new \IntegerNet\Solr\Request\ApplicationContext(
            new AttributeRepository($this->getLoadedCacheReader($storeId)), $storeConfig->getResultsConfig(), $storeConfig->getAutosuggestConfig(), new NullEventDispatcher(), $logger
        );
        switch ($requestMode) {
            case self::REQUEST_MODE_SEARCHTERM_SUGGEST:
                $applicationContext->setQuery($this->request);
                $factory = new SearchTermSuggestRequestFactory($applicationContext, $this->getSolrResource(), $this->request->getStoreId());
                break;
            case self::REQUEST_MODE_CATEGORY_SUGGEST:
                $applicationContext->setQuery($this->request);
                $factory = new CategorySuggestRequestFactory($applicationContext, $this->getSolrResource(), $this->request->getStoreId());
                break;
            case self::REQUEST_MODE_CMS_PAGE_SUGGEST:
                $applicationContext->setQuery($this->request);
                $factory = new CmsPageSuggestRequestFactory($applicationContext, $this->getSolrResource(), $this->request->getStoreId());
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
        $storeConfig = $this->getStoreConfigByStoreId($storeId);
        return new AutosuggestResult(
            $this->request->getStoreId(),
            $storeConfig->getGeneralConfig(),
            $storeConfig->getAutosuggestConfig(),
            $storeConfig->getCategoryConfig(),
            $this->request,
            new SearchUrl($storeConfig->getStoreConfig()),
            new CategoryRepository($this->getLoadedCacheReader($storeId)),
            new AttributeRepository($this->getLoadedCacheReader($storeId)),
            $this->getSolrRequest(self::REQUEST_MODE_AUTOSUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_SEARCHTERM_SUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_CATEGORY_SUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_CMS_PAGE_SUGGEST)
        );
    }

    /**
     * @param $storeId
     * @return \IntegerNet\Solr\Implementor\Config
     */
    private function getStoreConfigByStoreId($storeId)
    {
        $storeConfig = $this->getLoadedCacheReader($storeId)->getConfig($storeId);
        return $storeConfig;
    }

    /**
     * @return CacheReader
     */
    public function getCacheReader()
    {
        return new CacheReader($this->cacheStorage);
    }


    private function getLoadedCacheReader($storeId)
    {
        if ($this->loadedCacheReader === null) {
            $this->loadedCacheReader = $this->getCacheReader();
        }
        try {
            $this->loadedCacheReader->load($storeId);
        } catch (CacheItemNotFoundException $e) {
            $this->initCacheFromApp();
            $this->loadedCacheReader->load($storeId);
        }
        return $this->loadedCacheReader;
    }

    private function getAutosuggestBlock()
    {
        $storeId = $this->request->getStoreId();
        $highlighter = new HtmlStringHighlighter();
        $templateRepository = new TemplateRepository($this->getLoadedCacheReader($storeId));
        return new AutosuggestBlock($storeId, $this, $this, $templateRepository, $highlighter);
    }

    /**
     * @param LoggerInterface $customLogger
     * @return AutosuggestController
     */
    public function getAutosuggestController(LoggerInterface $customLogger = null)
    {
        $config = $this->getStoreConfigByStoreId($this->request->getStoreId());
        $generalConfig = $config->getGeneralConfig();
        $storeConfig = $config->getStoreConfig();
        return new AutosuggestController(
            $generalConfig,
            $this->getAutosuggestBlock(),
            $customLogger !== null ? $customLogger : $this->getLogger($generalConfig, $storeConfig, 'solr.log')
        );
    }

    /**
     * Use callback to load application (Magento) and write cache
     */
    private function initCacheFromApp()
    {
        $loader = $this->loadApplicationCallback;
        /** @var AppFactory $appFactory */
        $appFactory = $loader();
        $appFactory->getCacheWriter()->write($appFactory->getStoreConfig());
    }

    /**
     * @param GeneralConfig $generalConfig
     * @param StoreConfig $storeConfig
     * @param $filename
     * @return null|\Psr\Log\NullLogger
     */
    private function getLogger(GeneralConfig $generalConfig, StoreConfig $storeConfig, $filename)
    {
        $loggerFactory = new LoggerFactory($generalConfig, $storeConfig);
        return $loggerFactory->getLogger($filename);
    }
}