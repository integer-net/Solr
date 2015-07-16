<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Result
{
    /** @var null|IntegerNet_Solr_Model_Resource_Solr */
    protected $_resource = null;

    /** @var null|IntegerNet_Solr_Model_Resource_Solr_Service */
    protected $_solrResult = null;

    /** @var null|Mage_Catalog_Block_Product_List_Toolbar */
    protected $_toolbarBlock = null;

    protected $_filters = array();

    /**
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::getResourceModel('integernet_solr/solr');
        }

        return $this->_resource;
    }

    /**
     * Call Solr server twice: Once without fuzzy search, once with (if configured)
     *
     * @param $storeId
     * @return Apache_Solr_Response
     */
    public function getSolrResult($storeId = null)
    {
        if (is_null($this->_solrResult)) {
            if (is_null($storeId)) {
                $storeId = Mage::app()->getStore()->getId();
            }

            $pageSize = $this->_getPageSize();
            $firstItemNumber = $this->_getCurrentPage() * $pageSize;
            $lastItemNumber = $firstItemNumber + $pageSize;

            if ($this->_isAutosuggest()) {
                $isFuzzyActive = Mage::getStoreConfigFlag('integernet_solr/fuzzy/is_active_autosuggest');
            } else {
                $isFuzzyActive = Mage::getStoreConfigFlag('integernet_solr/fuzzy/is_active');
            }
            if ($this->_getCurrentSort() != 'position') {
                $result = $this->_getResultFromRequest($storeId, $lastItemNumber, $isFuzzyActive);
            } else {
                $result = $this->_getResultFromRequest($storeId, $lastItemNumber, false);

                $numberResults = sizeof($result->response->docs);
                $numberDuplicates = 0;
                if ($isFuzzyActive) {

                    $fuzzyResult = $this->_getResultFromRequest($storeId, $lastItemNumber, true);

                    if ($numberResults < $lastItemNumber) {

                        $foundProductIds = array();
                        foreach($result->response->docs as $nonFuzzyDoc) { /** @var Apache_Solr_Document $nonFuzzyDoc */
                            $field = $nonFuzzyDoc->getField('product_id');
                            $foundProductIds[] = $field['value'];
                        }

                        foreach($fuzzyResult->response->docs as $fuzzyDoc) { /** @var Apache_Solr_Document $fuzzyDoc */
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
        if (!$this->_getToolbarBlock()) {
            return 0;
        }
        return $this->_getToolbarBlock()->getCurrentPage() - 1;
    }

    /**
     * @return int
     */
    protected function _getCurrentSort()
    {
        if (!$this->_getToolbarBlock()) {
            return 'position';
        }
        return $this->_getToolbarBlock()->getCurrentOrder();
    }

    /**
     * @return int
     */
    protected function _getCurrentSortDirection()
    {
        if (!$this->_getToolbarBlock()) {
            return 'desc';
        }
        if ($this->_getCurrentSort() == 'position') {
            switch(strtolower($this->_getToolbarBlock()->getCurrentDirection())) {
                case 'desc':
                    return 'asc';
                default:
                    return 'desc';
            }
        }
        return $this->_getToolbarBlock()->getCurrentDirection();
    }

    /**
     * @return int
     */
    protected function _getPageSize()
    {
        if (!$this->_getToolbarBlock()) {
            return intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_product_suggestions'));
        }
        return $this->_getToolbarBlock()->getLimit();
    }

    /**
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    protected function _getToolbarBlock()
    {
        if (is_null($this->_toolbarBlock)) {
            if (!Mage::app()->getLayout()) {
                $this->_toolbarBlock = false;
            } else {
                $this->_toolbarBlock = Mage::app()->getLayout()->getBlock('product_list_toolbar');
            }
        }
        return $this->_toolbarBlock;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function _getParams($storeId, $fuzzy = true)
    {
        $params = array(
            'q.op' => Mage::getStoreConfig('integernet_solr/results/search_operator'),
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


            if (($priceStepsize = Mage::getStoreConfig('integernet_solr/results/price_step_size'))
                && ($maxPrice = Mage::getStoreConfig('integernet_solr/results/max_price'))) {
                $params['facet.range'] = 'price_f';
                $params['f.price_f.facet.range.start'] = 0;
                $params['f.price_f.facet.range.end'] = $maxPrice;
                $params['f.price_f.facet.range.gap'] = $priceStepsize;
            }

            if (Mage::getStoreConfigFlag('integernet_solr/results/use_custom_price_intervals')
                && ($customPriceIntervals = Mage::getStoreConfig('integernet_solr/results/custom_price_intervals'))) {
                $params['f.price_f.facet.interval.set'] = array();
                $lowerBorder = 0;
                foreach(explode(',', $customPriceIntervals) as $upperBorder) {
                    $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%f]', $lowerBorder, $upperBorder);
                    $lowerBorder = $upperBorder;
                }
                $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%s]', $lowerBorder, '*');
            } else if (($priceStepsize = Mage::getStoreConfig('integernet_solr/results/price_step_size'))
                && ($maxPrice = Mage::getStoreConfig('integernet_solr/results/max_price'))) {
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
            $params['mm'] = '100%';
        }

        if (!$this->_getToolbarBlock()) {
            $params['rows'] = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_product_suggestions'));
        }

        return $params;
    }

    /**
     * @return array
     */
    protected function _getFacetFieldCodes()
    {
        $codes = array('category');
        foreach(Mage::helper('integernet_solr')->getFilterableInSearchAttributes() as $attribute) {
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
        $query = Mage::helper('catalogsearch')->getQuery();
        $queryText = $query->getQueryText();
        if ($query->getSynonymFor()) {
            $queryText = $query->getSynonymFor();
        }

        $transportObject = new Varien_Object(array(
            'query_text' => $queryText,
        ));

        Mage::dispatchEvent('integernet_solr_update_query_text', array('transport' => $transportObject));

        $queryText = $transportObject->getQueryText();

        if ($this->_isAutosuggest()) {
            $isFuzzyActive = Mage::getStoreConfigFlag('integernet_solr/fuzzy/is_active_autosuggest');
            $sensitivity = Mage::getStoreConfig('integernet_solr/fuzzy/sensitivity_autosuggest');
        } else {
            $isFuzzyActive = Mage::getStoreConfigFlag('integernet_solr/fuzzy/is_active');
            $sensitivity = Mage::getStoreConfig('integernet_solr/fuzzy/sensitivity');
        }
        if ($allowFuzzy && $isFuzzyActive) {
            $queryText .= '~' . floatval($sensitivity);
        } else {
            $queryText = 'text_plain:"' . $queryText . '"~100';
        }
        return $queryText;
    }

    /**
     * @return string
     */
    protected function _getSortParam()
    {
        switch ($this->_getCurrentSort()) {
            case 'position':
                $param = 'score';
                break;
            case 'price':
                $param = 'price_f';
                break;
            default:
                $param = $this->_getCurrentSort() . '_s';
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
        $filterQuery = 'store_id:' . $storeId;

        foreach($this->getFilters() as $attributeCode => $value) {
            $filterQuery .= ' AND ' . $attributeCode . ':' . $value;
        }

        return $filterQuery;
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
        if (Mage::getStoreConfigFlag('integernet_solr/results/use_custom_price_intervals')
            && $customPriceIntervals = Mage::getStoreConfig('integernet_solr/results/custom_price_intervals')) {
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

        if (Mage::getStoreConfigFlag('integernet_solr/general/log')) {

            $this->_logResult($result, microtime(true) - $startTime);
        }

        Mage::dispatchEvent('integernet_solr_after_search_request', array('result' => $result));

        return $result;
    }

    /**
     * @return mixed
     */
    protected function _isAutosuggest()
    {
        return Mage::registry('is_autosuggest');
    }
}