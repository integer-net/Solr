<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Query;

use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Query\Query;
use IntegerNet\Solr\Query\QueryBuilder;
use IntegerNet\Solr\Query\SearchString;

class SearchTermSuggestQueryBuilder implements QueryBuilder
{
    /**
     * @var $paramsBuilder ParamsBuilder
     */
    private $paramsBuilder;
    /**
     * @var $storeId int
     */
    private $storeId;

    /**
     * @param ParamsBuilder $paramsBuilder
     * @param int $storeId
     */
    public function __construct(ParamsBuilder $paramsBuilder, $storeId)
    {
        $this->paramsBuilder = $paramsBuilder;
        $this->storeId = $storeId;
    }

    public function build()
    {
        return new Query($this->storeId, '*', 0, 0, $this->paramsBuilder->buildAsArray());
    }

    /**
     * @return ParamsBuilder
     */
    public function getParamsBuilder()
    {
        return $this->paramsBuilder;
    }
}