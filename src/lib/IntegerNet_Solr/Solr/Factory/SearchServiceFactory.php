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
     * @param ApplicationContext $applicationContext
     * @param SolrResource $resource
     */
    public function __construct(ApplicationContext $applicationContext, SolrResource $resource, $storeId)
    {
        parent::__construct($applicationContext, $resource, $storeId);
        $this->fuzzyConfig = $applicationContext->getFuzzyConfig();
        $this->query = $applicationContext->getQuery();
    }

    public function createParamsBuilder()
    {
        return new SearchParamsBuilder(
            $this->getAttributeRepository(),
            $this->getFilterQueryBuilder(),
            $this->getPagination(),
            $this->getResultsConfig(),
            $this->getStoreId()
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