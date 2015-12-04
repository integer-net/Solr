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

use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;
use Apache_Solr_Response;
use IntegerNet\Solr\Query\CategoryQueryBuilder;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Result\Logger;
use Psr\Log\LoggerInterface;

class CategoryService implements SolrService
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
     * @var ParamsBuilder
     */
    private $paramsBuilder;
    /**
     * @var $logger Logger
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
        $this->logger = new Logger($logger);
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
     * @return Apache_Solr_Response
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

        /* @var Apache_Solr_Response $result */
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
     * @return ResourceFacade
     */
    private function getResource()
    {
        return $this->resource;
    }

}