<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Request;

use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use Apache_Solr_Response;
use IntegerNet\Solr\Request\HasFilter;
use IntegerNet\Solr\Request\Request;
use IntegerNet\SolrCategories\Query\CategoryParamsBuilder;
use IntegerNet\SolrCategories\Query\CategoryQueryBuilder;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Resource\SolrResponse;
use IntegerNet\Solr\Resource\LoggerDecorator;
use Psr\Log\LoggerInterface;

class CategoryRequest implements Request, HasFilter
{
    /**
     * @var $resource ResourceFacade
     */
    private $resource;
    /**
     * @var $queryBuilder CategoryQueryBuilder
     */
    private $queryBuilder;
    /**
     * @var $logger LoggerDecorator
     */
    private $logger;
    /**
     * @var $eventDispatcher EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param LoggerInterface $logger
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(ResourceFacade $resource, CategoryQueryBuilder $queryBuilder, LoggerInterface $logger, EventDispatcher $eventDispatcher)
    {
        $this->queryBuilder = $queryBuilder;
        $this->resource = $resource;
        $this->logger = new LoggerDecorator($logger);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return CategoryParamsBuilder
     */
    private function getParamsBuilder()
    {
        return $this->queryBuilder->getParamsBuilder();
    }

    /**
     * @return FilterQueryBuilder
     */
    public function getFilterQueryBuilder()
    {
        return $this->getParamsBuilder()->getFilterQueryBuilder();
    }


    /**
     * @param string[] $activeFilterAttributeCodes
     * @return SolrResponse
     */
    public function doRequest($activeFilterAttributeCodes = array())
    {
        $query = $this->queryBuilder
            ->setAttributeToReset('')
            ->build();
        $transportObject = new Transport(array(
            'store_id' => $this->getParamsBuilder()->getStoreId(),
            'query_text' => $query->getQueryText(),
            'start_item' => 0,
            'page_size' => $this->getParamsBuilder()->getPageSize() * $this->getParamsBuilder()->getCurrentPage(),
            'params' => $query->getParams(),
        ));

        $this->eventDispatcher->dispatch('integernet_solr_before_category_request', array('transport' => $transportObject));

        $startTime = microtime(true);

        $result = $this->getResource()->search(
            $transportObject->getStoreId(),
            $transportObject->getQueryText(),
            $transportObject->getStartItem(), // Start item
            $transportObject->getPageSize(), // Items per page
            $transportObject->getParams()
        );

        $this->logger->logResult($result, microtime(true) - $startTime);

        foreach ($activeFilterAttributeCodes as $attributeCode) {

            $query = $this->queryBuilder
                ->setAttributeToReset($attributeCode)
                ->build();

            $transportObject = new Transport(array(
                'store_id' => $this->getParamsBuilder()->getStoreId(),
                'query_text' => $query->getQueryText(),
                'start_item' => 0,
                'page_size' => 0,
                'params' => $query->getParams(),
            ));

            $this->eventDispatcher->dispatch('integernet_solr_before_category_request', array('transport' => $transportObject));

            $parentResult = $this->getResource()->search(
                $transportObject->getStoreId(),
                $transportObject->getQueryText(),
                $transportObject->getStartItem(), // Start item
                $transportObject->getPageSize(), // Items per page
                $transportObject->getParams()
            );

            switch ($attributeCode) {
                case 'category':
                    $facetCode = $attributeCode;
                    break;
                default:
                    $facetCode = $attributeCode . '_facet';
            }
            if (isset($parentResult->facet_counts->facet_fields->{$facetCode})) {
                $result->facet_counts->facet_fields->{$facetCode} = $parentResult->facet_counts->facet_fields->{$facetCode};
            }
            if ($attributeCode == 'price' && isset($parentResult->facet_counts->facet_ranges->price_f)) {
                $result->facet_counts->facet_ranges->price_f = $parentResult->facet_counts->facet_ranges->price_f;
            }
            if ($attributeCode == 'price' && isset($parentResult->facet_counts->facet_intervals->price_f)) {
                $result->facet_counts->facet_intervals->price_f = $parentResult->facet_counts->facet_intervals->price_f;
            }
        }

        $this->eventDispatcher->dispatch('integernet_solr_after_category_request', array('result' => $result));

        return $this->sliceResult($result);
    }

    /**
     * Remove all but last page from multipage result
     *
     * @param SolrResponse $result
     * @return Apache_Solr_Response
     */
    private function sliceResult(SolrResponse $result)
    {
        $pageSize = $this->getParamsBuilder()->getPageSize();
        $firstItemNumber = ($this->getParamsBuilder()->getCurrentPage() - 1) * $pageSize;
        if ($firstItemNumber > 0) {
            $result->response->docs = array_slice($result->response->docs, $firstItemNumber, $pageSize);
        }
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