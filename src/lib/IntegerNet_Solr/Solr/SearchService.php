<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr;

use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use Apache_Solr_Response;
use Apache_Solr_Document;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Result\Logger;
use Psr\Log\LoggerInterface;
use Varien_Object;
use IntegerNet_Solr_Model_Query;

class SearchService implements SolrService
{
    /**
     * @var $resource SolrResource
     */
    private $resource;
    /**
     * @var $query IntegerNet_Solr_Model_Query
     */
    private $query;
    /**
     * @var $pagination Pagination
     */
    private $pagination;
    /**
     * @var $fuzzyConfig FuzzyConfig
     */
    private $fuzzyConfig;
    /**
     * @var $paramsBuilder ParamsBuilder
     */
    private $paramsBuilder;
    /**
     * @var $eventDispatcher EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var $logger Logger
     */
    private $logger;
    /**
     * Second run to Solr, when the first search hasn't found anything!
     * @var $foundNoResults bool
     */
    private $foundNoResults = false;

    /**
     * SearchService constructor.
     * @param SolrResource $resource
     * @param IntegerNet_Solr_Model_Query $query
     * @param Pagination $pagination
     * @param FuzzyConfig $fuzzyConfig
     * @param ParamsBuilder $paramsBuilder
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(SolrResource $resource, IntegerNet_Solr_Model_Query $query, Pagination $pagination, FuzzyConfig $fuzzyConfig, ParamsBuilder $paramsBuilder, EventDispatcher $eventDispatcher, LoggerInterface $logger)
    {
        $this->resource = $resource;
        $this->query = $query;
        $this->pagination = $pagination;
        $this->fuzzyConfig = $fuzzyConfig;
        $this->paramsBuilder = $paramsBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = new Logger($logger);
    }

    /**
     * @return ParamsBuilder
     */
    public function getParamsBuilder()
    {
        return $this->paramsBuilder;
    }

    /**
     * @return Apache_Solr_Response
     */
    public function doRequest()
    {
        $storeId = $this->paramsBuilder->getStoreId();
        $pageSize = $this->paramsBuilder->getPageSize() * $this->paramsBuilder->getCurrentPage();
        $isFuzzyActive = $this->fuzzyConfig->isActive();
        $minimumResults = $this->fuzzyConfig->getMinimumResults();
        if ($this->getCurrentSort() != 'position') {
            $result = $this->getResultFromRequest($storeId, $pageSize, $isFuzzyActive);
            return $this->sliceResult($result);
        } else {
            $result = $this->getResultFromRequest($storeId, $pageSize, false);

            $numberResults = sizeof($result->response->docs);
            $numberDuplicates = 0;
            if ($isFuzzyActive && (($minimumResults == 0) || ($numberResults < $minimumResults))) {

                $fuzzyResult = $this->getResultFromRequest($storeId, $pageSize, true);

                if ($numberResults < $pageSize) {

                    $foundProductIds = array();
                    foreach ($result->response->docs as $nonFuzzyDoc) {
                        /* @var $nonFuzzyDoc Apache_Solr_Document */
                        $field = $nonFuzzyDoc->getField('product_id');
                        $foundProductIds[] = $field['value'];
                    }

                    foreach ($fuzzyResult->response->docs as $fuzzyDoc) {
                        /* @var $fuzzyDoc Apache_Solr_Document */
                        $field = $fuzzyDoc->getField('product_id');
                        if (!in_array($field['value'], $foundProductIds)) {
                            $result->response->docs[] = $fuzzyDoc;
                            if (++$numberResults >= $pageSize) {
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

                $this->mergeFacetFieldCounts($result, $fuzzyResult);
                $this->mergePriceData($result, $fuzzyResult);
            }

            if (sizeof($result->response->docs) == 0) {
                $this->foundNoResults = true;
                $check = explode(' ', $this->query->getUserQueryText());
                if (count($check) > 1) {
                    $result = $this->getResultFromRequest($storeId, $pageSize, false);
                }
                $this->foundNoResults = false;
                return $this->sliceResult($result);
            }
            return $this->sliceResult($result);
        }
    }

    /**
     * Remove all but last page from multipage result
     *
     * @param Apache_Solr_Response $result
     * @return Apache_Solr_Response
     */
    private function sliceResult(Apache_Solr_Response $result)
    {
        $pageSize = $this->paramsBuilder->getPageSize();
        $firstItemNumber = ($this->paramsBuilder->getCurrentPage() - 1) * $pageSize;
        if ($firstItemNumber > 0) {
            $result->response->docs = array_slice($result->response->docs, $firstItemNumber, $pageSize);
        }
        return $result;
    }
    /**
     * @return string
     */
    private function getCurrentSort()
    {
        return $this->pagination->getCurrentOrder();
    }


    /**
     * Merge facet counts of both results and store them into $result
     *
     * @todo extract to result class
     *
     * @param $result
     * @param $fuzzyResult
     */
    private function mergeFacetFieldCounts($result, $fuzzyResult)
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
     * @todo extract to result class
     *
     * @param $result
     * @param $fuzzyResult
     */
    private function mergePriceData($result, $fuzzyResult)
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
     * @param int $storeId
     * @param int $pageSize
     * @param boolean $fuzzy
     * @return \Apache_Solr_Response
     */
    private function getResultFromRequest($storeId, $pageSize, $fuzzy = true)
    {
        //TODO create TransportObject class, compatible to Varien_Object
        $transportObject = new Varien_Object(array(
            'store_id' => $storeId,
            'query_text' => $this->getQueryText($fuzzy),
            'start_item' => 0,
            'page_size' => $pageSize,
            'params' => $this->getParams($storeId, $fuzzy),
        ));

        $this->eventDispatcher->dispatch('integernet_solr_before_search_request', array('transport' => $transportObject));

        $startTime = microtime(true);

        /* @var Apache_Solr_Response $result */
        $result = $this->getResource()->search(
            $storeId,
            $transportObject->getQueryText(),
            $transportObject->getStartItem(), // Start item
            $transportObject->getPageSize(), // Items per page
            $transportObject->getParams()
        );

        $this->logger->logResult($result, microtime(true) - $startTime);

        $this->logger->debug((($fuzzy) ? 'Fuzzy Search' : 'Normal Search'));
        $this->logger->debug('Query over all searchable fields: ' . $transportObject['query_text']);
        $this->logger->debug('Filter Query: ' . $transportObject['params']['fq']);

        $this->eventDispatcher->dispatch('integernet_solr_after_search_request', array('result' => $result));

        return $result;
    }

    /**
     * @return string
     */
    private function getQueryText($allowFuzzy = true)
    {
        return $this->query->getSolrQueryText($allowFuzzy, $this->foundNoResults);
    }

    /**
     * @param $storeId
     * @param $fuzzy
     * @return array
     */
    private function getParams($storeId, $fuzzy = true)
    {
        return $this->paramsBuilder->buildAsArray($storeId, $fuzzy);
    }

    /**
     * @return SolrResource
     */
    private function getResource()
    {
        return $this->resource;
    }
}