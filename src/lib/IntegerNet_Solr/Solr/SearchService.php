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
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use Apache_Solr_Response;
use Apache_Solr_Document;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Query\SearchQueryBuilder;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Result\Logger;
use Psr\Log\LoggerInterface;

class SearchService implements SolrService
{
    /**
     * @var $resource ResourceFacade
     */
    private $resource;
    /**
     * @var $queryBuilder SearchQueryBuilder
     */
    private $queryBuilder;
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
     * @param ResourceFacade $resource
     * @param SearchQueryBuilder $queryBuilder
     * @param Pagination $pagination
     * @param FuzzyConfig $fuzzyConfig
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(ResourceFacade $resource, SearchQueryBuilder $queryBuilder, Pagination $pagination, FuzzyConfig $fuzzyConfig, EventDispatcher $eventDispatcher, LoggerInterface $logger)
    {
        $this->resource = $resource;
        $this->queryBuilder = $queryBuilder;
        $this->pagination = $pagination;
        $this->fuzzyConfig = $fuzzyConfig;
        $this->paramsBuilder = $queryBuilder->getParamsBuilder();
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = new Logger($logger);
    }

    /**
     * @return ParamsBuilder
     */
    private function getParamsBuilder()
    {
        return $this->paramsBuilder;
    }

    /**
     * @return FilterQueryBuilder
     */
    public function getFilterQueryBuilder()
    {
        return $this->getParamsBuilder()->getFilterQueryBuilder();
    }

    /**
     * @return Apache_Solr_Response
     */
    public function doRequest()
    {
        $pageSize = $this->getParamsBuilder()->getPageSize() * $this->getParamsBuilder()->getCurrentPage();
        $isFuzzyActive = $this->fuzzyConfig->isActive();
        $minimumResults = $this->fuzzyConfig->getMinimumResults();
        if ($this->getCurrentSort() != 'position') {
            $result = $this->getResultFromRequest($pageSize, $isFuzzyActive);
            return $this->sliceResult($result);
        } else {
            $result = $this->getResultFromRequest($pageSize, false);

            $numberResults = sizeof($result->response->docs);
            $numberDuplicates = 0;
            if ($isFuzzyActive && (($minimumResults == 0) || ($numberResults < $minimumResults))) {

                $fuzzyResult = $this->getResultFromRequest( $pageSize, true);

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
                $check = explode(' ', $this->queryBuilder->getSearchString()->getRawString());
                if (count($check) > 1) {
                    $result = $this->getResultFromRequest($pageSize, false);
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
        $pageSize = $this->getParamsBuilder()->getPageSize();
        $firstItemNumber = ($this->getParamsBuilder()->getCurrentPage() - 1) * $pageSize;
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
     * @param int $pageSize
     * @param boolean $fuzzy
     * @return \Apache_Solr_Response
     */
    private function getResultFromRequest($pageSize, $fuzzy = true)
    {
        $query = $this->queryBuilder->setAllowFuzzy($fuzzy)->setBroaden($this->foundNoResults)->build();
        $transportObject = new Transport(array(
            'store_id' => $this->getParamsBuilder()->getStoreId(),
            'query_text' => $query->getQueryText(),
            'start_item' => 0,
            'page_size' => $pageSize,
            'params' => $query->getParams(),
        ));

        $this->eventDispatcher->dispatch('integernet_solr_before_search_request', array('transport' => $transportObject));

        $startTime = microtime(true);

        /* @var Apache_Solr_Response $result */
        $result = $this->getResource()->search(
            $transportObject->getStoreId(),
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
     * @return ResourceFacade
     */
    private function getResource()
    {
        return $this->resource;
    }
}