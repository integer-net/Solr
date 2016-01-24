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

use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\Solr\Query\SearchString;
use IntegerNet\Solr\Request\ApplicationContext;
use IntegerNet\Solr\Request\RequestFactory;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrSuggest\Query\SearchTermSuggestParamsBuilder;
use IntegerNet\SolrSuggest\Query\SearchTermSuggestQueryBuilder;

class SearchTermSuggestRequestFactory extends RequestFactory
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
     * @param ApplicationContext $applicationContext
     * @param ResourceFacade $resource
     * @param int $storeId
     */
    public function __construct(ApplicationContext $applicationContext, ResourceFacade $resource, $storeId)
    {
        parent::__construct($applicationContext, $resource, $storeId);
        $this->query = $applicationContext->getQuery();
        $this->autosuggestConfig = $applicationContext->getAutosuggestConfig();
    }

    protected function createQueryBuilder()
    {
        return new SearchTermSuggestQueryBuilder($this->createParamsBuilder(), $this->getStoreId());
    }

    protected function createParamsBuilder()
    {
        return new SearchTermSuggestParamsBuilder(
            new SearchString($this->query->getUserQueryText()), $this->autosuggestConfig, $this->getStoreId());
    }

    /**
     * @return \IntegerNet\Solr\Request\Request
     */
    public function createRequest()
    {
        return new SearchTermSuggestRequest(
            $this->getResource(),
            $this->createQueryBuilder(),
            $this->getEventDispatcher(),
            $this->getLogger()
        );
    }

}