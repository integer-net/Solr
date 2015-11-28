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
use IntegerNet\Solr\SolrService;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Implementor\Attribute;
use Psr\Log\NullLogger;

/**
 * @todo don't use it as singleton
 * @todo implement factory / autosuggest stub
 */
class IntegerNet_Solr_Model_Result
{
    /**
     * @var $_solrService SolrService
     */
    protected $_solrService;
    /**
     * @var $storeId int
     */
    protected $_storeId;
    /**
     * @var $_config Config
     */
    protected $_config;
    /**
     * @var $_pagination Pagination
     */
    protected $_pagination;
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
        $this->_storeId = Mage::app()->getStore()->getId();
        $this->_config = new IntegerNet_Solr_Model_Config_Store($this->_storeId);
        if ($this->_config->getGeneralConfig()->isLog()) {
            $logger = Mage::helper('integernet_solr/log');
        } else {
            $logger = new NullLogger;
        }
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $this->_pagination = Mage::getModel('integernet_solr/result_pagination_toolbar', $block);
        } else {
            $this->_pagination = Mage::getModel('integernet_solr/result_pagination_autosuggest', $this->_config->getAutosuggestConfig());
        }

        $isAutosuggest = Mage::registry('is_autosuggest');
        $isCategoryPage = Mage::helper('integernet_solr')->isCategoryPage();
        $this->_filterQueryBuilder = new FilterQueryBuilder();
        $this->_filterQueryBuilder->setIsCategoryPage($isCategoryPage);
        /** @var SolrServiceFactory $factory */
        /**
         * @todo inject IntegerNet_Solr_Interface_Factory as ImplementorFactory into SolrServiceFactory
         *       include create methods for pagination, attributeconfig, eventdispatcher, ...
         */
        if ($isCategoryPage) {
            $factory = new CategoryServiceFactory(
                Mage::helper('integernet_solr/factory')->getSolrResource(),
                Mage::helper('integernet_solr'),
                $this->_filterQueryBuilder,
                $this->_pagination,
                $this->_config->getResultsConfig(),
                $logger,
                Mage::helper('integernet_solr'),
                Mage::registry('current_category')->getId()
            );
        } elseif ($isAutosuggest) {
            $factory = new AutosuggestServiceFactory(
                Mage::helper('integernet_solr/factory')->getSolrResource(),
                Mage::helper('integernet_solr'),
                $this->_filterQueryBuilder,
                $this->_pagination,
                $this->_config->getResultsConfig(),
                $logger,
                Mage::helper('integernet_solr'),
                $this->_config->getFuzzyAutosuggestConfig(),
                Mage::getModel('integernet_solr/query', $isAutosuggest)
            );
        } else {
            $factory = new SearchServiceFactory(
                Mage::helper('integernet_solr/factory')->getSolrResource(),
                Mage::helper('integernet_solr'),
                $this->_filterQueryBuilder,
                $this->_pagination,
                $this->_config->getResultsConfig(),
                $logger,
                Mage::helper('integernet_solr'),
                $this->_config->getFuzzySearchConfig(),
                Mage::getModel('integernet_solr/query', $isAutosuggest)
            );
        }
        $this->_solrService = $factory->createSolrService();

    }


    /**
     * Call Solr server twice: Once without fuzzy search, once with (if configured)
     *
     * @return Apache_Solr_Response
     */
    public function getSolrResult()
    {
        if (is_null($this->_solrResult)) {
            $storeId = $this->_storeId;

            $pageSize = $this->_getPageSize();
            $firstItemNumber = $this->_getCurrentPage() * $pageSize;
            $lastItemNumber = $firstItemNumber + $pageSize;

            $result = $this->_solrService->doRequest($storeId, $lastItemNumber);

            if ($firstItemNumber > 0) {
                $result->response->docs = array_slice($result->response->docs, $firstItemNumber, $pageSize);
            }

            $this->_solrResult = $result;
        }

        return $this->_solrResult;
    }

    /**
     * @return int
     */
    protected function _getCurrentPage()
    {
        return $this->_pagination->getCurrentPage() - 1;
    }



    /**
     * @return int
     */
    protected function _getPageSize()
    {
        return $this->_pagination->getPageSize();
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

    public function resetSearch()
    {
        $this->_solrResult = null;
        $this->_filterQueryBuilder = new FilterQueryBuilder();
    }



}