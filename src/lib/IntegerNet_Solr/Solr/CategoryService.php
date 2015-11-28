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

use IntegerNet\Solr\Implementor\EventDispatcher;
use Apache_Solr_Response;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Result\Logger;
use Psr\Log\LoggerInterface;
use Varien_Object;
use IntegerNet\Solr\SolrResource;

class CategoryService implements SolrService
{
    /**
     * @var $categoryId int
     */
    private $categoryId;
    /**
     * @var $resource SolrResource
     */
    private $resource;
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
     * @param int $categoryId
     * @param LoggerInterface $logger
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct($categoryId, SolrResource $resource, ParamsBuilder $paramsBuilder, LoggerInterface $logger, EventDispatcher $eventDispatcher)
    {
        $this->categoryId = $categoryId;
        $this->resource = $resource;
        $this->paramsBuilder = $paramsBuilder;
        $this->logger = new Logger($logger);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param int $storeId
     * @param int $pageSize
     * @return Apache_Solr_Response
     */
    public function doRequest($storeId, $pageSize)
    {
        $transportObject = new Varien_Object(array(
            'store_id' => $storeId,
            'query_text' => 'category_' . $this->categoryId . '_position_i:*',
            'start_item' => 0,
            'page_size' => $pageSize,
            'params' => $this->getParams($storeId),
        ));

        $this->eventDispatcher->dispatch('integernet_solr_before_category_request', array('transport' => $transportObject));

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

        $this->eventDispatcher->dispatch('integernet_solr_after_category_request', array('result' => $result));

        return $result;
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