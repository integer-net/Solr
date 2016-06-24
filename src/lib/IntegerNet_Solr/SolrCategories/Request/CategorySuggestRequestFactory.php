<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Request;

use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\Solr\Query\SearchString;
use IntegerNet\Solr\Request\ApplicationContext;
use IntegerNet\Solr\Request\RequestFactory;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrCategories\Query\CategorySuggestParamsBuilder;
use IntegerNet\SolrCategories\Query\CategorySuggestQueryBuilder;
use IntegerNet\SolrCategories\Request\CategorySuggestRequest;

class CategorySuggestRequestFactory extends RequestFactory
{
    /**
     * @var HasUserQuery
     */
    private $query;
    /**
     * @var \IntegerNet\Solr\Config\AutosuggestConfig
     */
    private $autosuggestConfig;
    /**
     * @var \IntegerNet\Solr\Config\ResultsConfig
     */
    private $resultsConfig;

    /**
     * @param ApplicationContext $applicationContext
     * @param ResourceFacade $resource
     * @param int $storeId
     */
    public function __construct(ApplicationContext $applicationContext, ResourceFacade $resource, $storeId)
    {
        parent::__construct($applicationContext, $resource, $storeId);
        $this->query = $applicationContext->getQuery();
        $this->autosuggestConfig = $applicationContext->getAutosuggestConfig();
        $this->resultsConfig = $applicationContext->getResultsConfig();
    }

    protected function createQueryBuilder()
    {
        return new CategorySuggestQueryBuilder(
            new SearchString($this->getQuery()->getUserQueryText()),
            $this->createParamsBuilder(),
            $this->getStoreId(),
            $this->getEventDispatcher(),
            $this->autosuggestConfig
        );
    }

    protected function createParamsBuilder()
    {
        return new CategorySuggestParamsBuilder(
            new SearchString($this->query->getUserQueryText()), $this->autosuggestConfig, $this->resultsConfig, $this->getStoreId());
    }

    /**
     * @return \IntegerNet\Solr\Request\Request
     */
    public function createRequest()
    {
        return new CategorySuggestRequest(
            $this->getResource(),
            $this->createQueryBuilder(),
            $this->getEventDispatcher(),
            $this->getLogger()
        );
    }

    /**
     * @return HasUserQuery
     */
    protected function getQuery()
    {
        return $this->query;
    }

}