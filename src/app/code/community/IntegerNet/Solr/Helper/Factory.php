<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Resource\ResourceFacade;
use Psr\Log\NullLogger;
use IntegerNet\Solr\Request\RequestFactory;
use IntegerNet\Solr\Request\SearchRequestFactory;
use IntegerNet\SolrCategories\Request\CategoryRequestFactory;
use IntegerNet\SolrSuggest\Request\AutosuggestRequestFactory;
use IntegerNet\SolrSuggest\Request\SearchTermSuggestRequestFactory;
use IntegerNet\Solr\Request\ApplicationContext;
use IntegerNet\Solr\Indexer\ProductIndexer;

class IntegerNet_Solr_Helper_Factory implements IntegerNet_Solr_Interface_Factory
{

    /**
     * Returns new configured Solr recource. Instantiation separate from RequestFactory
     * for easy mocking in integration tests
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $storeConfig = $this->getStoreConfig();
        return new ResourceFacade($storeConfig);
    }

    /**
     * Returns new product indexer.
     *
     * @return ProductIndexer
     */
    public function getProductIndexer()
    {
        $defaultStoreId = Mage::app()->getStore(true)->getId();
        return new ProductIndexer(
            $defaultStoreId,
            $this->getStoreConfig(),
            $this->getSolrResource(),
            Mage::helper('integernet_solr'),
            Mage::getSingleton('integernet_solr/bridge_attributeRepository'),
            Mage::getModel('integernet_solr/bridge_categoryRepository'),
            Mage::getModel('integernet_solr/bridge_productRepository'),
            Mage::getModel('integernet_solr/bridge_productRenderer')
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
            $logger = Mage::helper('integernet_solr/log');
            if ($logger instanceof IntegerNet_Solr_Helper_Log) {
                $logger->setFile(
                    $requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST ? 'solr_suggest.log' : 'solr.log'
                );
            }
        } else {
            $logger = new NullLogger;
        }

        $isCategoryPage = Mage::helper('integernet_solr')->isCategoryPage();
        $applicationContext = new ApplicationContext(
            Mage::getSingleton('integernet_solr/bridge_attributeRepository'),
            $config->getResultsConfig(),
            $config->getAutosuggestConfig(),
            Mage::helper('integernet_solr'),
            $logger
        );
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $pagination = Mage::getModel('integernet_solr/bridge_pagination_toolbar', $block);
            $applicationContext->setPagination($pagination);
        }
        /** @var RequestFactory $factory */
        if ($requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST) {
            $applicationContext->setQuery(Mage::helper('integernet_solr/searchterm'));
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
                ->setQuery(Mage::helper('integernet_solr'));
            $factory = new AutosuggestRequestFactory(
                $applicationContext,
                $this->getSolrResource(),
                $storeId
            );
        } else {
            $applicationContext
                ->setFuzzyConfig($config->getFuzzySearchConfig())
                ->setQuery(Mage::helper('integernet_solr'));
            $factory = new SearchRequestFactory(
                $applicationContext,
                $this->getSolrResource(),
                $storeId
            );
        }
        return $factory->createRequest();
    }

    /**
     * @return array
     */
    public function getStoreConfig()
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

}