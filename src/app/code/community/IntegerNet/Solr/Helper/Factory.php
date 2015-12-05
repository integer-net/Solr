<?php
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\SolrService;
use Psr\Log\NullLogger;
use IntegerNet\Solr\Factory\SolrServiceFactory;
use IntegerNet\Solr\Factory\SearchServiceFactory;
use IntegerNet\Solr\Factory\CategoryServiceFactory;
use IntegerNet\Solr\Factory\AutosuggestServiceFactory;
use IntegerNet\Solr\Factory\ApplicationContext;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Helper_Factory implements IntegerNet_Solr_Interface_Factory
{
    /**
     * Returns new configured Solr recource. Instantiation separate from SolrServiceFactory
     * for easy mocking in integration tests
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $storeConfig = $this->_getStoreConfig();
        return new ResourceFacade($storeConfig);
    }

    /**
     * Returns new Solr service (search, autosuggest or category service, depending on application state)
     *
     * @return SolrService
     */
    public function getSolrService()
    {
        $storeId = Mage::app()->getStore()->getId();
        $config = new IntegerNet_Solr_Model_Config_Store($storeId);
        if ($config->getGeneralConfig()->isLog()) {
            $logger = Mage::helper('integernet_solr/log');
        } else {
            $logger = new NullLogger;
        }
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $pagination = Mage::getModel('integernet_solr/result_pagination_toolbar', $block);
        } else {
            $pagination = Mage::getModel('integernet_solr/result_pagination_autosuggest', $config->getAutosuggestConfig());
        }

        $isAutosuggest = Mage::registry('is_autosuggest');
        $isCategoryPage = Mage::helper('integernet_solr')->isCategoryPage();
        $applicationContext = new ApplicationContext(
            Mage::helper('integernet_solr'),
            $config->getResultsConfig(),
            $pagination,
            Mage::helper('integernet_solr'),
            $logger
        );
        /** @var SolrServiceFactory $factory */
        if ($isCategoryPage) {
            $factory = new CategoryServiceFactory($applicationContext,
                $this->getSolrResource(),
                $storeId,
                Mage::registry('current_category')->getId()
            );
        } elseif ($isAutosuggest) {
            $applicationContext
                ->setFuzzyConfig($config->getFuzzyAutosuggestConfig())
                ->setQuery(Mage::helper('integernet_solr'));
            $factory = new AutosuggestServiceFactory(
                $applicationContext,
                $this->getSolrResource(),
                $storeId
            );
        } else {
            $applicationContext
                ->setFuzzyConfig($config->getFuzzySearchConfig())
                ->setQuery(Mage::helper('integernet_solr'));
            $factory = new SearchServiceFactory(
                $applicationContext,
                $this->getSolrResource(),
                $storeId
            );
        }
        return $factory->createSolrService();
    }

    /**
     * @return array
     */
    protected function _getStoreConfig()
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