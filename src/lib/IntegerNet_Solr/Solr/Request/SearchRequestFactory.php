<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Request;

use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Request\ApplicationContext;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\QueryBuilder;
use IntegerNet\Solr\Query\SearchQueryBuilder;
use IntegerNet\Solr\Query\SearchString;
use IntegerNet\Solr\Request\RequestFactory;
use IntegerNet\Solr\Resource\ResourceFacade;
use Psr\Log\LoggerInterface;
use IntegerNet\Solr\Query\SearchParamsBuilder;
use IntegerNet\Solr\Request\SearchRequest;

class SearchRequestFactory extends RequestFactory
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
     * @param ResourceFacade $resource
     * @param int $storeId
     */
    public function __construct(ApplicationContext $applicationContext, ResourceFacade $resource, $storeId)
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

    /**
     * @return \IntegerNet\Solr\Request\SearchRequest
     */
    public function createRequest()
    {
        return new SearchRequest(
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