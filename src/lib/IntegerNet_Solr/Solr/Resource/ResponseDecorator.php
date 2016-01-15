<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Resource;
use Apache_Solr_Response;

/**
 * Thin abstraction layer for Apache_Solr_Response, adds features for SolrResponse interface
 *
 * @package IntegerNet\Solr\Resource
 */
class ResponseDecorator implements SolrResponse
{
    /**
     * Do not rename this variable to $response, as it collides with Apache_Solr_Response->response
     *
     * @var Apache_Solr_Response
     */
    private $apacheSolrResponse;

    /**
     * @param Apache_Solr_Response $response
     */
    public function __construct(Apache_Solr_Response $response)
    {
        $this->apacheSolrResponse = $response;
    }

    function __get($name)
    {
        return $this->apacheSolrResponse->__get($name);
    }

    function __isset($name)
    {
        return $this->apacheSolrResponse->__isset($name);
    }

    /**
     * @param SolrResponse $other
     * @param int $pageSize
     * @return SolrResponse
     */
    public function merge(SolrResponse $other, $pageSize)
    {
        /** @var ResponseDecorator $result */
        $result = unserialize(serialize($this));
        $result->mergeDocuments($other, $pageSize);
        $result->mergeFacetFieldCounts($other);
        $result->mergePriceData($other);
        return $result;
    }

    /**
     * Merge documents from other response, keeping size below $pageSize
     *
     * @param $other
     * @param $pageSize
     */
    private function mergeDocuments($other, $pageSize)
    {
        $numberResults = sizeof($this->response->docs);
        if ($numberResults < $pageSize) {

            $foundProductIds = array();
            foreach ($this->response->docs as $nonFuzzyDoc) {
                /* @var $nonFuzzyDoc Apache_Solr_Document */
                $field = $nonFuzzyDoc->getField('product_id');
                $foundProductIds[] = $field['value'];
            }

            $numberDuplicates = 0;

            foreach ($other->response->docs as $fuzzyDoc) {
                /* @var $fuzzyDoc Apache_Solr_Document */
                $field = $fuzzyDoc->getField('product_id');
                if (!in_array($field['value'], $foundProductIds)) {
                    $this->response->docs[] = $fuzzyDoc;
                    if (++$numberResults >= $pageSize) {
                        break;
                    }
                } else {
                    $numberDuplicates++;
                }
            }

            $this->response->numFound = $this->response->numFound
                + $other->response->numFound
                - $numberDuplicates;
        } else {
            $this->response->numFound = max(
                $this->response->numFound,
                $other->response->numFound
            );
        }
    }

    /**
     * Merge facet counts from other response
     *
     * @param $other
     */
    private function mergeFacetFieldCounts($other)
    {
        $facetFields = (array)$other->facet_counts->facet_fields;

        foreach($facetFields as $facetName => $facetCounts) {
            $facetCounts = (array)$facetCounts;

            foreach($facetCounts as $facetId => $facetCount) {
                if (isset($this->facet_counts->facet_fields->$facetName->$facetId)) {
                    $this->facet_counts->facet_fields->$facetName->$facetId = max(
                        $this->facet_counts->facet_fields->$facetName->$facetId,
                        $facetCount
                    );
                } else {
                    $this->facet_counts->facet_fields->$facetName->$facetId = $facetCount;
                }
            }
        }

        if (isset($other->facet_counts->facet_ranges)) {

            $facetRanges = (array)$other->facet_counts->facet_ranges;

            foreach ($facetRanges as $facetName => $facetCounts) {
                $facetCounts = (array)$facetCounts->counts;

                if (!isset($this->facet_counts)) {
                    $this->facet_counts = new \stdClass();
                }
                if (!isset($this->facet_counts->facet_ranges)) {
                    $this->facet_counts->facet_ranges = new \stdClass();
                }
                if (!isset($this->facet_counts->facet_ranges->$facetName)) {
                    $this->facet_counts->facet_ranges->$facetName = new \stdClass();
                    $this->facet_counts->facet_ranges->$facetName->counts = new \stdClass();
                }

                foreach ($facetCounts as $facetId => $facetCount) {
                    if (isset($this->facet_counts->facet_ranges->$facetName->counts->$facetId)) {
                        $this->facet_counts->facet_ranges->$facetName->counts->$facetId = max(
                            $this->facet_counts->facet_ranges->$facetName->counts->$facetId,
                            $facetCount
                        );
                    } else {
                        $this->facet_counts->facet_ranges->$facetName->counts->$facetId = $facetCount;
                    }
                }
            }
        }

        if (isset($other->facet_counts->facet_intervals)) {

            $facetIntervals = (array)$other->facet_counts->facet_intervals;

            foreach ($facetIntervals as $facetName => $facetCounts) {
                $facetCounts = (array)$facetCounts;

                if (!isset($this->facet_counts)) {
                    $this->facet_counts = new \stdClass();
                }
                if (!isset($this->facet_counts->facet_intervals)) {
                    $this->facet_counts->facet_intervals = new \stdClass();
                }
                if (!isset($this->facet_counts->facet_intervals->$facetName)) {
                    $this->facet_counts->facet_intervals->$facetName = new \stdClass();
                }

                foreach ($facetCounts as $facetId => $facetCount) {
                    if (isset($this->facet_counts->facet_intervals->$facetName->$facetId)) {
                        $this->facet_counts->facet_intervals->$facetName->$facetId = max(
                            $this->facet_counts->facet_intervals->$facetName->$facetId,
                            $facetCount
                        );
                    } else {
                        $this->facet_counts->facet_intervals->$facetName->$facetId = $facetCount;
                    }
                }
            }
        }
    }

    /**
     * Merge price information (min, max, intervals) from other response
     *
     * @param $other
     */
    private function mergePriceData($other)
    {
        if (!isset($other->stats->stats_fields)) {
            return;
        }

        $statsFields = (array)$other->stats->stats_fields;

        foreach($statsFields as $fieldName => $fieldData) {

            if (!isset($this->stats)) {
                $this->stats = new \stdClass();
            }
            if (!isset($this->stats->stats_fields)) {
                $this->stats->stats_fields = new \stdClass();
            }
            if (!isset($this->stats->stats_fields->$fieldName)) {
                $this->stats->stats_fields->$fieldName = new \stdClass();
            }

            $fieldData = (array)$fieldData;
            if (isset($fieldData['min'])) {
                $this->stats->stats_fields->$fieldName->min = $fieldData['min'];
            }
            if (isset($fieldData['max'])) {
                $this->stats->stats_fields->$fieldName->max = $fieldData['max'];
            }
        }
    }

}