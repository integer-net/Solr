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
use IntegerNet\Solr\Query\SearchQueryBuilder;
use IntegerNet\Solr\Query\SearchString;
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
     * @var HasUserQuery
     */
    private $query;

    /**
     * @param ApplicationContext $applicationContext
     * @param SolrResource $resource
     * @param int $storeId
     */
    public function __construct(ApplicationContext $applicationContext, SolrResource $resource, $storeId)
    {
        parent::__construct($applicationContext, $resource, $storeId);
        $this->fuzzyConfig = $applicationContext->getFuzzyConfig();
        $this->query = $applicationContext->getQuery();
    }

    protected function createQueryBuilder()
    {
        return new SearchQueryBuilder(
            new SearchString($this->getQuery()->getUserQueryText()),
            $this->getFuzzyConfig(), $this->getAttributeRepository(), $this->getPagination(),
            $this->createParamsBuilder(), $this->getStoreId(), $this->getEventDispatcher()
        );
    }

    protected function createParamsBuilder()
    {
        return new SearchParamsBuilder(
            $this->getAttributeRepository(),
            $this->getFilterQueryBuilder(),
            $this->getPagination(),
            $this->getResultsConfig(),
            $this->getFuzzyConfig(),
            $this->getStoreId()
        );
    }

    public function createSolrService()
    {
        return new SearchService(
            $this->getResource(),
            $this->createQueryBuilder(),
            $this->getPagination(),
            $this->getFuzzyConfig(),
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
     * @return HasUserQuery
     */
    protected function getQuery()
    {
        return $this->query;
    }

}