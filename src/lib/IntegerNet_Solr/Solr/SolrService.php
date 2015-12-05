<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr;

use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Resource\SolrResponse;

interface SolrService
{
    /**
     * @return SolrResponse
     */
    public function doRequest();

    /**
     * @return FilterQueryBuilder
     */
    public function getFilterQueryBuilder();
}