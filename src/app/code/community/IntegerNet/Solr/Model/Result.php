<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
use IntegerNet\Solr\SolrService;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Implementor\Attribute;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @todo break into more classes
 * @todo don't use it as singleton
 * @todo implement factory for autosuggest stub
 * @todo extract to /lib
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
     * @var $_attributeRepository AttributeRepository
     */
    protected $_attributeRespository;
    /**
     * @var $_eventDispatcher EventDispatcher
     */
    protected $_eventDispatcher;
    /**
     * @var $_logger LoggerInterface
     */
    protected $_logger;
    /**
     * @var $_isCategoryPage bool
     */
    protected $_isCategoryPage;
    /**
     * @var $_categoryId int
     */
    protected $_categoryId;
    /**
     * @var $_query IntegerNet_Solr_Model_Query
     */
    protected $_query;
    /**
     * @var $_pagination Pagination
     */
    protected $_pagination;

    /**
     * @var $_resource null|IntegerNet_Solr_Model_Resource_Solr
     */
    protected $_resource = null;

    /**
     * @var $_solrResult null|IntegerNet_Solr_Service
     */
    protected $_solrResult = null;

    /**
     * @var ParamsBuilder
     */
    protected $_paramsBuilder;
    /**
     * @var $_filterQueryBuilder FilterQueryBuilder
     */
    protected $_filterQueryBuilder;

    /**
     * @todo use constructor injection as soon as this is not a Magento singleton anymore
     */
    function __construct()
    {
        $isAutosuggest = Mage::registry('is_autosuggest');
        $this->_storeId = Mage::app()->getStore()->getId();
        $this->_config = new IntegerNet_Solr_Model_Config_Store($this->_storeId);
        $this->_eventDispatcher = $this->_attributeRespository = Mage::helper('integernet_solr');
        if ($this->_config->getGeneralConfig()->isLog()) {
            $this->_logger = Mage::helper('integernet_solr/log');
        } else {
            $this->_logger = new NullLogger;
        }
        $this->_isCategoryPage = Mage::helper('integernet_solr')->isCategoryPage();
        if ($this->_isCategoryPage) {
            $this->_categoryId = Mage::registry('current_category')->getId();
        }
        $this->_query = Mage::getModel('integernet_solr/query', $isAutosuggest);
        $this->_filterQueryBuilder = new FilterQueryBuilder();
        $this->_filterQueryBuilder->setIsCategoryPage($this->_isCategoryPage);
        $this->_resource = Mage::helper('integernet_solr/factory')->getSolrResource();
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $this->_pagination = Mage::getModel('integernet_solr/result_pagination_toolbar', $block);
        } else {
            $this->_pagination = Mage::getModel('integernet_solr/result_pagination_autosuggest', $this->_config->getAutosuggestConfig());
        }
        if ($isAutosuggest) {
            $this->_paramsBuilder = new \IntegerNet\Solr\Query\AutosuggestParamsBuilder(
                $this->_attributeRespository,
                $this->_filterQueryBuilder,
                $this->_pagination,
                $this->_config->getResultsConfig()
            );
        } elseif ($this->_isCategoryPage) {
            $this->_paramsBuilder = new \IntegerNet\Solr\Query\CategoryParamsBuilder(
                $this->_attributeRespository,
                $this->_filterQueryBuilder,
                $this->_pagination,
                $this->_config->getResultsConfig(),
                $this->_categoryId
            );
        } else {
            $this->_paramsBuilder = new \IntegerNet\Solr\Query\SearchParamsBuilder(
                $this->_attributeRespository,
                $this->_filterQueryBuilder,
                $this->_pagination,
                $this->_config->getResultsConfig()
            );
        }


        if ($this->_isCategoryPage) {
            $this->_solrService = new \IntegerNet\Solr\CategoryService();
        } else {
            $this->_solrService = new \IntegerNet\Solr\SearchService(
                $this->_resource,
                $this->_query,
                $this->_pagination,
                $isAutosuggest ? $this->_config->getFuzzyAutosuggestConfig() : $this->_config->getFuzzySearchConfig(),
                $this->_paramsBuilder,
                $this->_eventDispatcher,
                $this->_logger
            );
        }

    }


    /**
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    protected function _getResource()
    {
        return $this->_resource;
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

            if ($this->_isCategoryPage) {
                $result = $this->_getCategoryResultFromRequest($storeId, $lastItemNumber);
            } else {
                $result = $this->_solrService->doRequest($storeId, $lastItemNumber);
            }


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
     * @param $storeId
     * @param $fuzzy
     * @return array
     */
    protected function _getParams($storeId, $fuzzy = true)
    {
        return $this->_paramsBuilder->buildAsArray($storeId, $fuzzy);
    }






    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @param int $value
     */
    public function addAttributeFilter($attribute, $value)
    {
        $this->_filterQueryBuilder->addAttributeFilter(new Attribute($attribute), $value);
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

    /**
     * @param Apache_Solr_Response $result
     * @param int $time in microseconds
     */
    protected function _logResult($result, $time)
    {
        $resultClone = unserialize(serialize($result));
        if (isset($resultClone->response->docs)) {
            foreach ($resultClone->response->docs as $key => $doc) {
                /* @var Apache_Solr_Document $doc */
                foreach ($doc->getFieldNames() as $fieldName) {
                    $field = $doc->getField($fieldName);
                    $value = $field['value'];
                    if (is_array($value)) {
                        foreach($value as $subKey => $subValue) {
                            $subValue = str_replace(array("\n", "\r"), '', $subValue);
                            if (strlen($subValue) > 50) {
                                $subValue = substr($subValue, 0, 50) . '...';
                                $value[$subKey] = $subValue;
                                $doc->setField($fieldName, $value);
                                $resultClone->response->docs[$key] = $doc;
                            }
                        }
                    } else {
                        $value = str_replace(array("\n", "\r"), '', $value);
                        if (strlen($value) > 50) {
                            $value = substr($value, 0, 50) . '...';
                            $doc->setField($fieldName, $value);
                            $resultClone->response->docs[$key] = $doc;
                        }
                    }
                }
            }
        }
        $this->_logger->debug($resultClone);
        $this->_logger->debug('Elapsed time: ' . $time . 's');
    }

    /**
     * @param int $storeId
     * @param int $pageSize
     * @return Apache_Solr_Response
     */
    protected function _getCategoryResultFromRequest($storeId, $pageSize)
    {
        $transportObject = new Varien_Object(array(
            'store_id' => $storeId,
            'query_text' => 'category_' . $this->_categoryId . '_position_i:*',
            'start_item' => 0,
            'page_size' => $pageSize,
            'params' => $this->_getParams($storeId),
        ));

        $this->_eventDispatcher->dispatch('integernet_solr_before_category_request', array('transport' => $transportObject));

        $startTime = microtime(true);

        /* @var Apache_Solr_Response $result */
        $result = $this->_getResource()->search(
            $storeId,
            $transportObject->getQueryText(),
            $transportObject->getStartItem(), // Start item
            $transportObject->getPageSize(), // Items per page
            $transportObject->getParams()
        );

        if ($this->_config->getGeneralConfig()->isLog()) {

            $this->_logResult($result, microtime(true) - $startTime);
        }

        $this->_eventDispatcher->dispatch('integernet_solr_after_category_request', array('result' => $result));

        return $result;
    }



}