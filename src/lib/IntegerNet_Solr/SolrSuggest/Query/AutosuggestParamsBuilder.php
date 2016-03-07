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

use IntegerNet\Solr\Query\AbstractParamsBuilder;

final class AutosuggestParamsBuilder extends AbstractParamsBuilder
{
    public function buildAsArray($attributeToReset = '')
    {
        $params = parent::buildAsArray($attributeToReset);
        $params['rows'] = $this->pagination->getPageSize();

        return $params;
    }

    /**
     * Leave out facet parameters
     *
     * @param $params
     * @return mixed
     */
    protected function addFacetParams($params)
    {
        return $params;
    }

}