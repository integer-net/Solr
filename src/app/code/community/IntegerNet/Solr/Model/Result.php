<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

/**
 * @todo extract interfaces to Magento: facets, category, log, events
 * @todo don't use it as singleton
 * @todo implement factory for autosuggest stub
 * @todo extract to /lib
 */
class IntegerNet_Solr_Model_Result
{
    protected $_isAutosuggest;
    /**
     * @var int
     */
    protected $_storeId;
    /**
     * @var IntegerNet_Solr_Implementor_Config
     */
    protected $_config;
    /**
     * @var IntegerNet_Solr_Implementor_AttributeRepository
     */
    protected $_attributeRespository;
    /**
     * @var bool
     */
    protected $_isCategoryPage;
    /**
     * @var IntegerNet_Solr_Model_Query
     */
    protected $_query;
    /**
     * @var IntegerNet_Solr_Implementor_Pagination
     */
    protected $_pagination;

    /** @var null|IntegerNet_Solr_Model_Resource_Solr */
    protected $_resource = null;

    /** @var null|IntegerNet_Solr_Service */
    protected $_solrResult = null;

    protected $_filters = array();

    /**
     * Filter Query string
     * @var null|string
     */
    protected $_filterQuery = null;

    /**
     * last executed search query
     * @var string
     */
    protected $_lastQueryText = '';

    /**
     * Second run to Solr, when the first search hasn't found anything!
     * @var bool
     */
    private $_foundNoResults = false;

    /**
     * @todo use constructor injection as soon as this is not a Magento singleton anymore
     */
    function __construct()
    {
        $this->_isAutosuggest = Mage::registry('is_autosuggest');
        $this->_storeId = Mage::app()->getStore()->getId();
        $this->_config = new IntegerNet_Solr_Model_Config_Store($this->_storeId);
        $this->_attributeRespository = Mage::helper('integernet_solr');
        $this->_isCategoryPage = Mage::helper('integernet_solr')->isCategoryPage();
        $this->_query = Mage::getModel('integernet_solr/query', $this->_isAutosuggest);
        $this->_resource = Mage::helper('integernet_solr/factory')->getSolrResource();
        if (Mage::app()->getLayout() && $block = Mage::app()->getLayout()->getBlock('product_list_toolbar')) {
            $this->_pagination = Mage::getModel('integernet_solr/result_pagination_toolbar', $block);
        } else {
            $this->_pagination = Mage::getModel('integernet_solr/result_pagination_autosuggest', $this->_config->getAutosuggestConfig());
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

                if ($this->_isAutosuggest()) {
                    $isFuzzyActive = $this->_config->getFuzzyAutosuggestConfig()->isActive();
                    $minimumResults = $this->_config->getFuzzyAutosuggestConfig()->getMinimumResults();
                } else {
                    $isFuzzyActive = $this->_config->getFuzzySearchConfig()->isActive();
                    $minimumResults = $this->_config->getFuzzySearchConfig()->getMinimumResults();
                }
                if ($this->_getCurrentSort() != 'position') {
                    $result = $this->_getResultFromRequest($storeId, $lastItemNumber, $isFuzzyActive);
                } else {
                    $result = $this->_getResultFromRequest($storeId, $lastItemNumber, false);

                    $numberResults = sizeof($result->response->docs);
                    $numberDuplicates = 0;
                    if ($isFuzzyActive && (($minimumResults == 0) || ($numberResults < $minimumResults))) {

                        $fuzzyResult = $this->_getResultFromRequest($storeId, $lastItemNumber, true);

                        if ($numberResults < $lastItemNumber) {

                            $foundProductIds = array();
                            foreach ($result->response->docs as $nonFuzzyDoc) {
                                /** @var Apache_Solr_Document $nonFuzzyDoc */
                                $field = $nonFuzzyDoc->getField('product_id');
                                $foundProductIds[] = $field['value'];
                            }

                            foreach ($fuzzyResult->response->docs as $fuzzyDoc) {
                                /** @var Apache_Solr_Document $fuzzyDoc */
                                $field = $fuzzyDoc->getField('product_id');
                                if (!in_array($field['value'], $foundProductIds)) {
                                    $result->response->docs[] = $fuzzyDoc;
                                    if (++$numberResults >= $lastItemNumber) {
                                        break;
                                    }
                                } else {
                                    $numberDuplicates++;
                                }
                            }

                            $result->response->numFound = $result->response->numFound
                                + $fuzzyResult->response->numFound
                                - $numberDuplicates;
                        } else {
                            $result->response->numFound = max(
                                $result->response->numFound,
                                $fuzzyResult->response->numFound
                            );
                        }

                        $this->_mergeFacetFieldCounts($result, $fuzzyResult);
                        $this->_mergePriceData($result, $fuzzyResult);
                    }

                    if (sizeof($result->response->docs) == 0) {
                        $this->_foundNoResults = true;
                        $check = explode(' ', $this->_query->getUserQueryText());
                        if (count($check) > 1) {
                            $result = $this->_getResultFromRequest($storeId, $lastItemNumber, false);
                        }
                        $this->_foundNoResults = false;
                    }
                }
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
     * @return string
     */
    protected function _getCurrentSort()
    {
        return $this->_pagination->getCurrentOrder();
    }

    /**
     * @return int
     */
    protected function _getCurrentSortDirection()
    {
        $direction = $this->_pagination->getCurrentDirection();

        if ($this->_getCurrentSort() == 'position') {
            if (!$this->_isCategoryPage) {
                switch (strtolower($direction)) {
                    case 'desc':
                        return 'asc';
                    default:
                        return 'desc';
                }
            }
        }
        return $direction;
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
     * @return array
     */
    protected function _getParams($storeId, $fuzzy = true)
    {
        $resultsConfig = $this->_config->getResultsConfig();
        $params = array(
            'q.op' => $resultsConfig->getSearchOperator(),
            'fq' => $this->_getFilterQuery($storeId),
            'fl' => 'result_html_autosuggest_nonindex,score,sku_s,name_s,product_id',
            'sort' => $this->_getSortParam(),
            'facet' => 'true',
            'facet.sort' => 'true',
            'facet.mincount' => '1',
            'facet.field' => $this->_getFacetFieldCodes(),
            'defType' => 'edismax',
        );

        if (!$this->_isAutosuggest()) {
            $params['fl'] = 'result_html_list_nonindex,result_html_grid_nonindex,score,sku_s,name_s,product_id';
            $params['facet.interval'] = 'price_f';
            $params['stats'] = 'true';
            $params['stats.field'] = 'price_f';


            if (($priceStepsize = $resultsConfig->getPriceStepSize())
                && ($maxPrice = $resultsConfig->getMaxPrice())) {
                $params['facet.range'] = 'price_f';
                $params['f.price_f.facet.range.start'] = 0;
                $params['f.price_f.facet.range.end'] = $maxPrice;
                $params['f.price_f.facet.range.gap'] = $priceStepsize;
            }

            if ($resultsConfig->isUseCustomPriceIntervals()
                && ($customPriceIntervals = $resultsConfig->getCustomPriceIntervals())) {
                $params['f.price_f.facet.interval.set'] = array();
                $lowerBorder = 0;
                foreach($customPriceIntervals as $upperBorder) {
                    $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%f]', $lowerBorder, $upperBorder);
                    $lowerBorder = $upperBorder;
                }
                $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%s]', $lowerBorder, '*');
            } else if (($priceStepsize = $resultsConfig->getPriceStepSize())
                && ($maxPrice = $resultsConfig->getMaxPrice())) {
                $params['f.price_f.facet.interval.set'] = array();
                $lowerBorder = 0;
                for ($upperBorder = $priceStepsize; $upperBorder <= $maxPrice; $upperBorder += $priceStepsize) {
                    $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%f]', $lowerBorder, $upperBorder);
                    $lowerBorder = $upperBorder;
                }
                $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%s]', $lowerBorder, '*');
            }
        }

        if (!$fuzzy) {
            $params['mm'] = '0%';
        }

        if ($this->_pagination instanceof IntegerNet_Solr_Model_Result_Pagination_Autosuggest) {
            $params['rows'] = $this->_pagination->getPageSize();
        }

        return $params;
    }

    /**
     * @return array
     */
    protected function _getFacetFieldCodes()
    {
        $codes = array('category');

        foreach($this->_attributeRespository->getFilterableAttributes() as $attribute) {
            $codes[] = $attribute->getAttributeCode() . '_facet';
        }
        return $codes;
    }

    /**
     * Merge facet counts of both results and store them into $result
     *
     * @param $result
     * @param $fuzzyResult
     */
    public function _mergeFacetFieldCounts($result, $fuzzyResult)
    {
        $facetFields = (array)$fuzzyResult->facet_counts->facet_fields;

        foreach($facetFields as $facetName => $facetCounts) {
            $facetCounts = (array)$facetCounts;

            foreach($facetCounts as $facetId => $facetCount) {
                if (isset($result->facet_counts->facet_fields->$facetName->$facetId)) {
                    $result->facet_counts->facet_fields->$facetName->$facetId = max(
                        $result->facet_counts->facet_fields->$facetName->$facetId,
                        $facetCount
                    );
                } else {
                    $result->facet_counts->facet_fields->$facetName->$facetId = $facetCount;
                }
            }
        }

        if (isset($fuzzyResult->facet_counts->facet_ranges)) {

            $facetRanges = (array)$fuzzyResult->facet_counts->facet_ranges;

            foreach ($facetRanges as $facetName => $facetCounts) {
                $facetCounts = (array)$facetCounts->counts;

                if (!isset($result->facet_counts)) {
                    $result->facet_counts = new stdClass();
                }
                if (!isset($result->facet_counts->facet_ranges)) {
                    $result->facet_counts->facet_ranges = new stdClass();
                }
                if (!isset($result->facet_counts->facet_ranges->$facetName)) {
                    $result->facet_counts->facet_ranges->$facetName = new stdClass();
                    $result->facet_counts->facet_ranges->$facetName->counts = new stdClass();
                }

                foreach ($facetCounts as $facetId => $facetCount) {
                    if (isset($result->facet_counts->facet_ranges->$facetName->counts->$facetId)) {
                        $result->facet_counts->facet_ranges->$facetName->counts->$facetId = max(
                            $result->facet_counts->facet_ranges->$facetName->counts->$facetId,
                            $facetCount
                        );
                    } else {
                        $result->facet_counts->facet_ranges->$facetName->counts->$facetId = $facetCount;
                    }
                }
            }
        }

        if (isset($fuzzyResult->facet_counts->facet_intervals)) {

            $facetIntervals = (array)$fuzzyResult->facet_counts->facet_intervals;

            foreach ($facetIntervals as $facetName => $facetCounts) {
                $facetCounts = (array)$facetCounts;

                if (!isset($result->facet_counts)) {
                    $result->facet_counts = new stdClass();
                }
                if (!isset($result->facet_counts->facet_intervals)) {
                    $result->facet_counts->facet_intervals = new stdClass();
                }
                if (!isset($result->facet_counts->facet_intervals->$facetName)) {
                    $result->facet_counts->facet_intervals->$facetName = new stdClass();
                }

                foreach ($facetCounts as $facetId => $facetCount) {
                    if (isset($result->facet_counts->facet_intervals->$facetName->$facetId)) {
                        $result->facet_counts->facet_intervals->$facetName->$facetId = max(
                            $result->facet_counts->facet_intervals->$facetName->$facetId,
                            $facetCount
                        );
                    } else {
                        $result->facet_counts->facet_intervals->$facetName->$facetId = $facetCount;
                    }
                }
            }
        }
    }

    /**
     * Merge price information (min, max, intervals) of both results and store them into $result
     *
     * @param $result
     * @param $fuzzyResult
     */
    public function _mergePriceData($result, $fuzzyResult)
    {
        if (!isset($fuzzyResult->stats->stats_fields)) {
            return;
        }

        $statsFields = (array)$fuzzyResult->stats->stats_fields;

        foreach($statsFields as $fieldName => $fieldData) {

            if (!isset($result->stats)) {
                $result->stats = new stdClass();
            }
            if (!isset($result->stats->stats_fields)) {
                $result->stats->stats_fields = new stdClass();
            }
            if (!isset($result->stats->stats_fields->$fieldName)) {
                $result->stats->stats_fields->$fieldName = new stdClass();
            }

            $fieldData = (array)$fieldData;
            if (isset($fieldData['min'])) {
                $result->stats->stats_fields->$fieldName->min = $fieldData['min'];
            }
            if (isset($fieldData['max'])) {
                $result->stats->stats_fields->$fieldName->max = $fieldData['max'];
            }
        }
    }

    /**
     * @return string
     */
    protected function _getQueryText($allowFuzzy = true)
    {
        $queryText = $this->_query->getSolrQueryText($allowFuzzy, $this->_foundNoResults);
        $this->_lastQueryText = $queryText;
        return $queryText;
    }

    /**
     * @return string
     */
    protected function _getSortParam()
    {
        $sortField = $this->_getCurrentSort();
        switch ($sortField) {
            case 'position':
                if ($this->_isCategoryPage) {
                    $param = 'category_' . Mage::registry('current_category')->getId() . '_position_i';
                } else {
                    $param = 'score';
                }
                break;
            case 'price':
                $param = 'price_f';
                break;
            default:
                $param = $sortField . '_s';
        }

        $param .= ' ' . $this->_getCurrentSortDirection();
        return $param;
    }

    /**
     * @param int $storeId
     * @return string
     */
    protected function _getFilterQuery($storeId)
    {
        if ($this->_filterQuery == null) {
            
            $filterQuery = 'store_id:' . $storeId;
            if ($this->_isCategoryPage) {
                $filterQuery .= ' AND is_visible_in_catalog_i:1';
            } else {
                $filterQuery .= ' AND is_visible_in_search_i:1';
            }

            foreach($this->getFilters() as $attributeCode => $value) {
                if (is_array($value)) {
                    $filterQuery .= ' AND (';
                    $filterQueryParts = array();
                    foreach($value as $singleValue) {
                        $filterQueryParts[] = $attributeCode . ':' . $singleValue;
                    }
                    $filterQuery .= implode(' OR ', $filterQueryParts);
                    $filterQuery .= ')';
                } else {
                    $filterQuery .= ' AND ' . $attributeCode . ':' . $value;
                }
            }

            $this->_filterQuery = $filterQuery;
        }

        return $this->_filterQuery;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @param int $value
     */
    public function addAttributeFilter($attribute, $value)
    {
        $this->_filters[$attribute->getAttributeCode() . '_facet'] = $value;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     */
    public function addCategoryFilter($category)
    {
        $this->_filters['category'] = $category->getId();
    }

    /**
     * @param int $range
     * @param int $index
     */
    public function addPriceRangeFilterByIndex($range, $index)
    {
        if ($this->_config->getResultsConfig()->isUseCustomPriceIntervals()
            && $customPriceIntervals = $this->_config->getResultsConfig()->getCustomPriceIntervals()) {
            $lowerBorder = 0;
            $i = 1;
            foreach(explode(',', $customPriceIntervals) as $upperBorder) {
                if ($i == $index) {
                    $this->_filters['price_f'] = sprintf('[%f TO %f]', $lowerBorder, $upperBorder);
                    return;
                }
                $i++;
                $lowerBorder = $upperBorder;
                continue;
            }
            $this->_filters['price_f'] = sprintf('[%f TO %s]', $lowerBorder, '*');
            return;

        } else {
            $maxPrice = $index * $range;
            $minPrice = $maxPrice - $range;
        }
        $this->_filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
    }

    /**
     * @param float $minPrice
     * @param float $maxPrice
     */
    public function addPriceRangeFilterByMinMax($minPrice, $maxPrice = 0.0)
    {
        if ($maxPrice) {
            $this->_filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
        } else {
            $this->_filters['price_f'] = sprintf('[%f TO *]', $minPrice);
        }
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * @return string
     */
    public function getLastQueryText()
    {
        return $this->_lastQueryText;
    }

    public function resetSearch()
    {
        $this->_solrResult = null;
        $this->_filters = array();
        $this->_filterQuery = null;
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
                /** @var Apache_Solr_Document $doc */
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
        Mage::log($resultClone, null, 'solr.log');
        Mage::log('Elapsed time: ' . $time . 's', null, 'solr.log');
    }

    /**
     * @param int $storeId
     * @param int $pageSize
     * @param boolean $fuzzy
     * @return Apache_Solr_Response
     */
    protected function _getResultFromRequest($storeId, $pageSize, $fuzzy = true)
    {
        $transportObject = new Varien_Object(array(
            'store_id' => $storeId,
            'query_text' => $this->_getQueryText($fuzzy),
            'start_item' => 0,
            'page_size' => $pageSize,
            'params' => $this->_getParams($storeId, $fuzzy),
        ));

        Mage::dispatchEvent('integernet_solr_before_search_request', array('transport' => $transportObject));

        $startTime = microtime(true);

        /** @var Apache_Solr_Response $result */
        $result = $this->_getResource()->search(
            $storeId,
            $transportObject->getQueryText(),
            $transportObject->getStartItem(), // Start item
            $transportObject->getPageSize(), // Items per page
            $transportObject->getParams()
        );

        if ($this->_config->getGeneralConfig()->isLog()) {
            $this->_logResult($result, microtime(true) - $startTime);

            Mage::log((($fuzzy) ? 'Fuzzy Search' : 'Normal Search'), null, 'solr.log');
            Mage::log('Query over all searchable fields:', null, 'solr.log');
            Mage::log($this->_lastQueryText, null, 'solr.log');
            Mage::log('Filter Query:', null, 'solr.log');
            Mage::log($this->_filterQuery, null, 'solr.log');
        }

        Mage::dispatchEvent('integernet_solr_after_search_request', array('result' => $result));

        return $result;
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
            'query_text' => 'category_' . Mage::registry('current_category')->getId() . '_position_i:*',
            'start_item' => 0,
            'page_size' => $pageSize,
            'params' => $this->_getParams($storeId),
        ));

        Mage::dispatchEvent('integernet_solr_before_category_request', array('transport' => $transportObject));

        $startTime = microtime(true);

        /** @var Apache_Solr_Response $result */
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

        Mage::dispatchEvent('integernet_solr_after_category_request', array('result' => $result));

        return $result;
    }

    /**
     * @return mixed
     */
    protected function _isAutosuggest()
    {
        return $this->_isAutosuggest;
    }
}