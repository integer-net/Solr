<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Request;

use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Request\Request;
use IntegerNet\Solr\Resource\LoggerDecorator;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Resource\SolrResponse;
use IntegerNet\SolrSuggest\Query\SearchTermSuggestQueryBuilder;
use Psr\Log\LoggerInterface;

class SearchTermSuggestRequest implements Request
{
    /**
     * @var $resource ResourceFacade
     */
    private $resource;
    /**
     * @var $queryBuilder SearchTermSuggestQueryBuilder
     */
    private $queryBuilder;
    /**
     * @var $eventDispatcher EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var $logger LoggerDecorator
     */
    private $logger;

    /**
     * SearchTermSuggestRequest constructor.
     * @param ResourceFacade $resource
     * @param SearchTermSuggestQueryBuilder $queryBuilder
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(ResourceFacade $resource, SearchTermSuggestQueryBuilder $queryBuilder, EventDispatcher $eventDispatcher, LoggerInterface $logger)
    {
        $this->resource = $resource;
        $this->queryBuilder = $queryBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = new LoggerDecorator($logger);
    }


    /**
     * @param string[] $activeFilterAttributeCodes
     * @return SolrResponse
     */
    public function doRequest($activeFilterAttributeCodes = array())
    {
        $startTime = microtime(true);
        $query = $this->queryBuilder->build();
        $result = $this->resource->search(
            $this->queryBuilder->getParamsBuilder()->getStoreId(),
            $query->getQueryText(),
            $query->getOffset(),
            $query->getLimit(),
            $query->getParams()
        );
        $this->logger->logSuggestion($result, microtime(true) - $startTime);
        return $result;
    }

}