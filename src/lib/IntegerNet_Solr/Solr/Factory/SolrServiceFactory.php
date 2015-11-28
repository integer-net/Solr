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
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\SolrResource;
use Psr\Log\LoggerInterface;

abstract class SolrServiceFactory
{
    /**
     * @var $resource SolrResource
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
     * @var $logger LoggerInterface
     */
    private $logger;
    /**
     * @var $eventDispatcher EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param ApplicationContext $applicationContext
     * @param SolrResource $resource
     * @param FilterQueryBuilder $filterQueryBuilder
     */
    public function __construct(ApplicationContext $applicationContext, SolrResource $resource, FilterQueryBuilder $filterQueryBuilder)
    {
        $this->resource = $resource;
        $this->attributeRepository = $applicationContext->getAttributeRepository();
        $this->filterQueryBuilder = $filterQueryBuilder;
        $this->pagination = $applicationContext->getPagination();
        $this->resultsConfig = $applicationContext->getResultsConfig();
        $this->logger = $applicationContext->getLogger();
        $this->eventDispatcher = $applicationContext->getEventDispatcher();
    }

    abstract public function createParamsBuilder();
    abstract public function createSolrService();

    /**
     * @return SolrResource
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

    

}