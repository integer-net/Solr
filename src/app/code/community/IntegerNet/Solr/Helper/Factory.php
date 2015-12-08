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
use IntegerNet\Solr\Factory\RequestFactory;
use IntegerNet\Solr\Factory\SearchRequestFactory;
use IntegerNet\SolrCategories\Factory\CategoryRequestFactory;
use IntegerNet\SolrSuggest\Factory\AutosuggestRequestFactory;
use IntegerNet\Solr\Factory\ApplicationContext;
use IntegerNet\SolrSuggest\Result\DummyPagination;

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
     * Returns new Solr service (search, autosuggest or category service, depending on application state)
     *
     * @return \IntegerNet\Solr\Request\Request
     */
    public function getSolrRequest()
    {
        $storeId = Mage::app()->getStore()->getId();
        $config = new IntegerNet_Solr_Model_Config_Store($storeId);
        if ($config->getGeneralConfig()->isLog()) {
            $logger = Mage::helper('integernet_solr/log');
        } else {
            $logger = new NullLogger;
        }
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $pagination = Mage::getModel('integernet_solr/bridge_pagination_toolbar', $block);
        } else {
            $pagination = new DummyPagination($config->getAutosuggestConfig());
        }

        $isAutosuggest = Mage::registry('is_autosuggest');
        $isCategoryPage = Mage::helper('integernet_solr')->isCategoryPage();
        $applicationContext = new ApplicationContext(
            Mage::getSingleton('integernet_solr/bridge_attributeRepository'),
            $config->getResultsConfig(),
            $pagination,
            Mage::helper('integernet_solr'),
            $logger
        );
        /** @var RequestFactory $factory */
        if ($isCategoryPage) {
            $factory = new CategoryRequestFactory($applicationContext,
                $this->getSolrResource(),
                $storeId,
                Mage::registry('current_category')->getId()
            );
        } elseif ($isAutosuggest) {
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
        $storeConfig = [];
        foreach (Mage::app()->getStores(true) as $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($store->getIsActive()) {
                $storeConfig[$store->getId()] = new IntegerNet_Solr_Model_Config_Store($store->getId());
            }
        }
        return $storeConfig;
    }


}