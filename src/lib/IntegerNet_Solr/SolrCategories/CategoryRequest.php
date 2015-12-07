<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrCategories;

use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use Apache_Solr_Response;
use IntegerNet\Solr\Request\HasFilter;
use IntegerNet\Solr\Request\Request;
use IntegerNet\SolrCategories\Query\CategoryQueryBuilder;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\ParamsBuilder;
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
     * @var $queryBuilder \IntegerNet\SolrCategories\Query\CategoryQueryBuilder
     */
    private $queryBuilder;
    /**
     * @var ParamsBuilder
     */
    private $paramsBuilder;
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
     * @return ParamsBuilder
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
     * @return SolrResponse
     */
    public function doRequest()
    {
        $query = $this->queryBuilder->build();
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