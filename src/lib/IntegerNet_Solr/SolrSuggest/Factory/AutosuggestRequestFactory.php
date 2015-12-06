<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Factory;

use IntegerNet\Solr\Factory\SearchRequestFactory;
use IntegerNet\SolrSuggest\Query\AutosuggestParamsBuilder;

class AutosuggestRequestFactory extends SearchRequestFactory
{
    public function createParamsBuilder()
    {
        return new AutosuggestParamsBuilder(
            $this->getAttributeRepository(),
            $this->getFilterQueryBuilder(),
            $this->getPagination(),
            $this->getResultsConfig(),
            $this->getFuzzyConfig(),
            $this->getStoreId()
        );
    }
}