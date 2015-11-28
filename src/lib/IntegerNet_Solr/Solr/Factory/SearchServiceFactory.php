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
use IntegerNet\Solr\Implementor\Query;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\SolrResource;
use Psr\Log\LoggerInterface;
use IntegerNet\Solr\Query\SearchParamsBuilder;
use IntegerNet\Solr\SearchService;

class SearchServiceFactory extends SolrServiceFactory
{
    /**
     * @var $fuzzyConfig FuzzyConfig
     */
    private $fuzzyConfig;

    /**
     * @var Query
     */
    private $query;
    /**
     * @param SolrResource $resource
     * @param AttributeRepository $attributeRepository
     * @param FilterQueryBuilder $filterQueryBuilder
     * @param Pagination $pagination
     * @param ResultsConfig $resultsConfig
     * @param LoggerInterface $logger
     * @param EventDispatcher $eventDispatcher
     * @param FuzzyConfig $fuzzyConfig
     * @param Query $query
     */
    public function __construct(SolrResource $resource, AttributeRepository $attributeRepository, FilterQueryBuilder $filterQueryBuilder, Pagination $pagination, ResultsConfig $resultsConfig, LoggerInterface $logger, EventDispatcher $eventDispatcher, FuzzyConfig $fuzzyConfig, Query $query)
    {
        parent::__construct($resource, $attributeRepository, $filterQueryBuilder, $pagination, $resultsConfig, $logger, $eventDispatcher);
        $this->fuzzyConfig = $fuzzyConfig;
        $this->query = $query;
    }

    public function createParamsBuilder()
    {
        return new SearchParamsBuilder(
            $this->getAttributeRepository(),
            $this->getFilterQueryBuilder(),
            $this->getPagination(),
            $this->getResultsConfig()
        );
    }

    public function createSolrService()
    {
        return new SearchService(
            $this->getResource(),
            $this->getQuery(),
            $this->getPagination(),
            $this->getFuzzyConfig(),
            $this->createParamsBuilder(),
            $this->getEventDispatcher(),
            $this->getLogger()
        );
    }

    /**
     * @return FuzzyConfig
     */
    protected function getFuzzyConfig()
    {
        return $this->fuzzyConfig;
    }

    /**
     * @return Query
     */
    protected function getQuery()
    {
        return $this->query;
    }
}