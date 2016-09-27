<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\SolrRequestFactory;
use IntegerNet\Solr\Indexer\ProductIndexer;
use IntegerNet\SolrCategories\Indexer\CategoryIndexer;
use IntegerNet\SolrCategories\Request\CategorySuggestRequestFactory;
use IntegerNet\SolrCategories\Request\CategorySearchRequestFactory;
use IntegerNet\SolrCms\Indexer\PageIndexer;
use IntegerNet\Solr\Request\ApplicationContext;
use IntegerNet\Solr\Request\RequestFactory;
use IntegerNet\Solr\Request\SearchRequestFactory;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrCategories\Request\CategoryRequestFactory;
use IntegerNet\SolrCms\Request\CmsPageSuggestRequestFactory;
use IntegerNet\SolrCms\Request\CmsPageRequestFactory;
use IntegerNet\SolrSuggest\CacheBackend\File\CacheItemPool as FileCacheBackend;
use IntegerNet\SolrSuggest\Implementor\Factory\AppFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\CacheReaderFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\AutosuggestResultFactory;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;
use IntegerNet\SolrSuggest\Plain\Cache\CacheWriter;
use IntegerNet\SolrSuggest\Plain\Cache\Convert\AttributesToSerializableAttributes;
use IntegerNet\SolrSuggest\Plain\Cache\PsrCache;
use IntegerNet\SolrSuggest\Request\AutosuggestRequestFactory;
use IntegerNet\SolrSuggest\Request\SearchTermSuggestRequestFactory;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use Psr\Log\NullLogger;

class IntegerNet_SolrPro_Helper_Factory implements SolrRequestFactory, AutosuggestResultFactory, CacheReaderFactory, AppFactory
{
    /**
     * @var IntegerNet_Solr_Model_Bridge_Factory
     */
    protected $_bridgeFactory;

    public function __construct()
    {
        $this->_bridgeFactory = Mage::getModel('integernet_solr/bridge_factory');
    }

    /**
     * Returns new configured Solr recource. Instantiation separate from RequestFactory
     * for easy mocking in integration tests
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $storeConfig = $this->getStoreConfigWithAdmin();
        return new ResourceFacade($storeConfig);
    }

    /**
     * Returns new product indexer.
     *
     * @return CategoryIndexer
     */
    public function getCategoryIndexer()
    {
        $defaultStoreId = Mage::app()->getStore(true)->getId();
        return new CategoryIndexer(
            $defaultStoreId,
            $this->getStoreConfig(),
            $this->getSolrResource(),
            $this->_getEventDispatcher(),
            $this->_bridgeFactory->createCategoryRepository(),
            $this->_bridgeFactory->createCategoryRenderer(),
            $this->_bridgeFactory->createStoreEmulation()
        );
    }

    /**
     * Returns new product indexer.
     *
     * @return PageIndexer
     */
    public function getPageIndexer()
    {
        $defaultStoreId = Mage::app()->getStore(true)->getId();
        return new PageIndexer(
            $defaultStoreId,
            $this->getStoreConfig(),
            $this->getSolrResource(),
            $this->_getEventDispatcher(),
            $this->_bridgeFactory->createPageRepository(),
            $this->_bridgeFactory->createPageRenderer(),
            $this->_bridgeFactory->createStoreEmulation()
        );
    }

    /**
     * Returns new Solr service (search, autosuggest or category service, depending on application state)
     *
     * @param int $requestMode
     * @return \IntegerNet\Solr\Request\Request
     */
    public function getSolrRequest($requestMode = self::REQUEST_MODE_AUTODETECT)
    {
        $storeId = Mage::app()->getStore()->getId();
        $config = new IntegerNet_Solr_Model_Config_Store($storeId);
        if ($config->getGeneralConfig()->isLog()) {
            $logger = $this->_getLogger();
            if ($logger instanceof IntegerNet_Solr_Helper_Log) {
                $logger->setFile(
                    $requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST ? 'solr_suggest.log' : 'solr.log'
                );
            }
        } else {
            $logger = new NullLogger;
        }

        $isCategoryPage = Mage::helper('integernet_solr')->page()->isCategoryPage();
        $applicationContext = new ApplicationContext(
            $this->_getAttributeRepository(),
            $config->getResultsConfig(),
            $config->getAutosuggestConfig(),
            $this->_getEventDispatcher(),
            $logger
        );
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $pagination = $this->_bridgeFactory->createPaginationToolbar($block);
            $applicationContext->setPagination($pagination);
        }
        /** @var RequestFactory $factory */
        if ($requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST) {
            $applicationContext->setQuery($this->_getSearchTermHelper());
            $factory = new SearchTermSuggestRequestFactory(
                $applicationContext,
                $this->getSolrResource(),
                $storeId);
        } elseif ($isCategoryPage) {
            $factory = new CategoryRequestFactory(
                $applicationContext,
                $this->getSolrResource(),
                $storeId,
                Mage::registry('current_category')->getId()
            );
        } elseif ($requestMode === self::REQUEST_MODE_AUTOSUGGEST) {
            $applicationContext
                ->setFuzzyConfig($config->getFuzzyAutosuggestConfig())
                ->setQuery($this->_getSearchTermSynonymHelper());
            $factory = new AutosuggestRequestFactory(
                $applicationContext,
                $this->getSolrResource(),
                $storeId,
                $this->_getEventDispatcher()
            );
        } else {
            switch ($requestMode) {
                case self::REQUEST_MODE_SEARCHTERM_SUGGEST:
                    $applicationContext->setQuery($this->_getSearchTermHelper());
                    $factory = new SearchTermSuggestRequestFactory($applicationContext, $this->getSolrResource(), $storeId);
                    break;
                case self::REQUEST_MODE_CATEGORY_SUGGEST:
                    $applicationContext->setQuery($this->_getSearchTermHelper());
                    $factory = new CategorySuggestRequestFactory($applicationContext, $this->getSolrResource(), $storeId);
                    break;
                case self::REQUEST_MODE_CMS_PAGE_SUGGEST:
                    $applicationContext->setQuery($this->_getSearchTermHelper());
                    $factory = new CmsPageSuggestRequestFactory($applicationContext, $this->getSolrResource(), $storeId);
                    break;
                case self::REQUEST_MODE_CATEGORY:
                    $applicationContext->setQuery($this->_getSearchTermHelper());
                    $factory = new CategorySuggestRequestFactory($applicationContext, $this->getSolrResource(), $storeId);
                    break;
                case self::REQUEST_MODE_CMS_PAGE:
                    $applicationContext
                        ->setCmsConfig($config->getCmsConfig())
                        ->setQuery($this->_getSearchTermHelper());
                    $factory = new CmsPageRequestFactory($applicationContext, $this->getSolrResource(), $storeId);
                    break;
                case self::REQUEST_MODE_CATEGORY_SEARCH:
                    $applicationContext
                        ->setCategoryConfig($config->getCategoryConfig())
                        ->setQuery($this->_getSearchTermHelper());
                    $factory = new CategorySearchRequestFactory($applicationContext, $this->getSolrResource(), $storeId);
                    break;
                default:
                    $applicationContext
                        ->setFuzzyConfig($config->getFuzzySearchConfig())
                        ->setQuery($this->_getSearchTermSynonymHelper());
                    $factory = new SearchRequestFactory(
                        $applicationContext,
                        $this->getSolrResource(),
                        $storeId
                    );
            }
        }
        return $factory->createRequest();
    }

    /**
     * @return Config[]
     */
    public function getStoreConfig()
    {
        $storeConfig = array();
        foreach (Mage::app()->getStores(false) as $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($store->getIsActive()) {
                $storeConfig[$store->getId()] = new IntegerNet_Solr_Model_Config_Store($store->getId());
            }
        }
        return $storeConfig;
    }

    /**
     * @return Config[]
     */
    public function getStoreConfigWithAdmin()
    {
        $storeConfig = array();
        foreach (Mage::app()->getStores(true) as $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($store->getIsActive()) {
                $storeConfig[$store->getId()] = new IntegerNet_Solr_Model_Config_Store($store->getId());
            }
        }
        return $storeConfig;
    }

    /**
     * @return IntegerNet_Solr_Model_Config_Store
     */
    public function getCurrentStoreConfig()
    {
        return new IntegerNet_Solr_Model_Config_Store(Mage::app()->getStore()->getId());
    }

    /**
     * @return AutosuggestResult
     */
    public function getAutosuggestResult()
    {
        $storeConfig = $this->getCurrentStoreConfig();
        return new AutosuggestResult(
            Mage::app()->getStore()->getId(),
            $storeConfig->getGeneralConfig(),
            $storeConfig->getAutosuggestConfig(),
            $storeConfig->getCategoryConfig(),
            $this->_getSearchTermHelper(),
            $this->_getSearchUrlHelper(),
            $this->_getSuggestCategoryRepository(),
            $this->_getAttributeRepository(),
            $this->getSolrRequest(self::REQUEST_MODE_AUTOSUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_SEARCHTERM_SUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_CATEGORY_SUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_CMS_PAGE_SUGGEST)
        );
    }

    /**
     * @return \IntegerNet\SolrSuggest\Plain\Cache\CacheReader
     */
    public function getCacheReader()
    {
        return new CacheReader($this->_getCacheStorage());
    }

    /**
     * @return \IntegerNet\SolrSuggest\Plain\Cache\CacheWriter
     */
    public function getCacheWriter()
    {
        $customHelperClass = new ReflectionClass(
            Mage::getConfig()->getHelperClassName('integernet_solrpro/custom')
        );
        $autosuggestConfigByStore = array_map(
            function (Config $config) {
                return $config->getAutosuggestConfig();
            },
            $this->getStoreConfig()
        );
        return new CacheWriter(
            $this->_getCacheStorage(),
            new AttributesToSerializableAttributes($this->_getAttributeRepository(), $this->_getEventDispatcher(), $autosuggestConfigByStore),
            $this->_getAutosuggestHelper(),
            new CustomHelperFactory($customHelperClass->getFileName(), $customHelperClass->getName()),
            $this->_getEventDispatcher(),
            $this->_getAutosuggestHelper()
        );
    }

    /**
     * Override this if you want to use a different cache backend. It is important to use the same
     * cache backend in the autosuggest.php bootstrap file
     *
     * @return \IntegerNet\SolrSuggest\Plain\Cache\CacheStorage
     */
    protected function _getCacheStorage()
    {
        return new PsrCache(new FileCacheBackend(Mage::getBaseDir('cache') . DS . 'integernet_solr'));
    }

    /**
     * @return \IntegerNet\Solr\Implementor\AttributeRepository
     */
    protected function _getAttributeRepository()
    {
        return $this->_bridgeFactory->getAttributeRepository();
    }

    /**
     * @return IntegerNet_Solr_Model_Bridge_CategoryRepository
     */
    protected function _getIndexCategoryRepository()
    {
        return $this->_bridgeFactory->getCategoryRepository();
    }

    /**
     * @return IntegerNet_Solr_Model_Bridge_CategoryRepository
     */
    protected function _getSuggestCategoryRepository()
    {
        return $this->_bridgeFactory->getCategoryRepository();
    }

    /**
     * @return IntegerNet_Solr_Helper_Event
     */
    protected function _getEventDispatcher()
    {
        return Mage::helper('integernet_solr/event');
    }

    /**
     * @return IntegerNet_Solr_Helper_SearchtermSynonym
     */
    protected function _getSearchTermSynonymHelper()
    {
        return Mage::helper('integernet_solr/searchtermSynonym');
    }

    /**
     * @return IntegerNet_Solr_Helper_Searchterm
     */
    protected function _getSearchTermHelper()
    {
        return Mage::helper('integernet_solr/searchterm');
    }

    /**
     * @return IntegerNet_Solr_Helper_SearchUrl
     */
    protected function _getSearchUrlHelper()
    {
        return Mage::helper('integernet_solr/searchUrl');
    }

    /**
     * @return IntegerNet_Solr_Helper_Log
     */
    protected function _getLogger()
    {
        return Mage::helper('integernet_solr/log');
    }

    /**
     * @return IntegerNet_Solr_Helper_Autosuggest
     */
    protected function _getAutosuggestHelper()
    {
        return Mage::helper('integernet_solrpro')->autosuggest();
    }
}