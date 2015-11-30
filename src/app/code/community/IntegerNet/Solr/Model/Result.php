<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
use IntegerNet\Solr\Factory\SolrServiceFactory;
use IntegerNet\Solr\Factory\SearchServiceFactory;
use IntegerNet\Solr\Factory\CategoryServiceFactory;
use IntegerNet\Solr\Factory\AutosuggestServiceFactory;
use IntegerNet\Solr\Factory\ApplicationContext;
use IntegerNet\Solr\SolrService;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Implementor\Attribute;
use Psr\Log\NullLogger;

class IntegerNet_Solr_Model_Result
{
    /**
     * @var $_solrService SolrService
     */
    protected $_solrService;
    /**
     * @var $_config Config
     */
    protected $_config;
    /**
     * @var $_filterQueryBuilder FilterQueryBuilder
     */
    protected $_filterQueryBuilder;
    /**
     * @var $_solrResult null|IntegerNet_Solr_Service
     */
    protected $_solrResult = null;

    function __construct()
    {
        $storeId = Mage::app()->getStore()->getId();
        $this->_config = new IntegerNet_Solr_Model_Config_Store($storeId);
        if ($this->_config->getGeneralConfig()->isLog()) {
            $logger = Mage::helper('integernet_solr/log');
        } else {
            $logger = new NullLogger;
        }
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $pagination = Mage::getModel('integernet_solr/result_pagination_toolbar', $block);
        } else {
            $pagination = Mage::getModel('integernet_solr/result_pagination_autosuggest', $this->_config->getAutosuggestConfig());
        }

        $isAutosuggest = Mage::registry('is_autosuggest');
        $isCategoryPage = Mage::helper('integernet_solr')->isCategoryPage();
        $applicationContext = new ApplicationContext(
            Mage::helper('integernet_solr'),
            $this->_config->getResultsConfig(),
            $pagination,
            Mage::helper('integernet_solr'),
            $logger
        );
        /** @var SolrServiceFactory $factory */
        if ($isCategoryPage) {
            $factory = new CategoryServiceFactory($applicationContext,
                Mage::helper('integernet_solr/factory')->getSolrResource(),
               $storeId,
                Mage::registry('current_category')->getId()
            );
        } elseif ($isAutosuggest) {
            $applicationContext
                ->setFuzzyConfig($this->_config->getFuzzyAutosuggestConfig())
                ->setQuery(Mage::helper('integernet_solr'));
            $factory = new AutosuggestServiceFactory(
                $applicationContext,
                Mage::helper('integernet_solr/factory')->getSolrResource(),
                $storeId
            );
        } else {
            $applicationContext
                ->setFuzzyConfig($this->_config->getFuzzySearchConfig())
                ->setQuery(Mage::helper('integernet_solr'));
            $factory = new SearchServiceFactory(
                $applicationContext,
                Mage::helper('integernet_solr/factory')->getSolrResource(),
                $storeId
            );
        }
        $this->_solrService = $factory->createSolrService();
        $this->_filterQueryBuilder = $this->_solrService->getFilterQueryBuilder();
    }


    /**
     * Call Solr server twice: Once without fuzzy search, once with (if configured)
     *
     * @return Apache_Solr_Response
     */
    public function getSolrResult()
    {
        if (is_null($this->_solrResult)) {
            $this->_solrResult = $this->_solrService->doRequest();
        }

        return $this->_solrResult;
    }


    /**
     * @param Attribute $attribute
     * @param int $value
     */
    public function addAttributeFilter($attribute, $value)
    {
        $this->_filterQueryBuilder->addAttributeFilter($attribute, $value);
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     */
    public function addCategoryFilter($category)
    {
        $this->_filterQueryBuilder->addCategoryFilter($category->getId());
    }

    /**
     * @param int $range
     * @param int $index
     */
    public function addPriceRangeFilterByIndex($range, $index)
    {
        if ($this->_config->getResultsConfig()->isUseCustomPriceIntervals()
            && $customPriceIntervals = $this->_config->getResultsConfig()->getCustomPriceIntervals()
        ) {
            $this->_filterQueryBuilder->addPriceRangeFilterWithCustomIntervals($index, $customPriceIntervals);
        } else {
            $this->_filterQueryBuilder->addPriceRangeFilter($range, $index);
        }
    }

    /**
     * @param float $minPrice
     * @param float $maxPrice
     */
    public function addPriceRangeFilterByMinMax($minPrice, $maxPrice = null)
    {
        $this->_filterQueryBuilder->addPriceRangeFilterByMinMax($minPrice, $maxPrice);
    }

}