<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Factory;

use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\QueryBuilder;
use IntegerNet\Solr\Resource\ResourceFacade;
use Psr\Log\LoggerInterface;

abstract class SolrServiceFactory
{
    /**
     * @var $resource ResourceFacade
     */
    private $resource;
    /**
     * @var $attributeRepository AttributeRepository
     */
    private $attributeRepository;
    /**
     * @var $filterQueryBuilder FilterQueryBuilder
     */
    private $filterQueryBuilder;
    /**
     * @var $pagination Pagination
     */
    private $pagination;
    /**
     * @var $resultsConfig ResultsConfig
     */
    private $resultsConfig;
    /**
     * @var $storeId int
     */
    private $storeId;
    /**
     * @var $logger LoggerInterface
     */
    private $logger;
    /**
     * @var $eventDispatcher EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param ApplicationContext $applicationContext
     * @param \IntegerNet\Solr\Resource\ResourceFacade $resource
     * @param int $storeId
     */
    public function __construct(ApplicationContext $applicationContext, ResourceFacade $resource, $storeId)
    {
        $this->resource = $resource;
        $this->attributeRepository = $applicationContext->getAttributeRepository();
        $this->filterQueryBuilder = new FilterQueryBuilder($applicationContext->getResultsConfig());
        $this->pagination = $applicationContext->getPagination();
        $this->resultsConfig = $applicationContext->getResultsConfig();
        $this->logger = $applicationContext->getLogger();
        $this->eventDispatcher = $applicationContext->getEventDispatcher();
        $this->storeId = $storeId;
    }

    abstract protected function createQueryBuilder();
    abstract protected function createParamsBuilder();
    abstract public function createSolrService();

    /**
     * @return \IntegerNet\Solr\Resource\ResourceFacade
     */
    protected function getResource()
    {
        return $this->resource;
    }

    /**
     * @return AttributeRepository
     */
    protected function getAttributeRepository()
    {
        return $this->attributeRepository;
    }

    /**
     * @return FilterQueryBuilder
     */
    protected function getFilterQueryBuilder()
    {
        return $this->filterQueryBuilder;
    }

    /**
     * @return Pagination
     */
    protected function getPagination()
    {
        return $this->pagination;
    }

    /**
     * @return ResultsConfig
     */
    protected function getResultsConfig()
    {
        return $this->resultsConfig;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return int
     */
    protected function getStoreId()
    {
        return $this->storeId;
    }

}