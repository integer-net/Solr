<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Implementor;

interface SearchUrl
{
    /**
     * Returns search URL for given user query text
     *
     * @param string $queryText
     * @param string[] $additionalParameters
     * @return string
     */
    public function getUrl($queryText, array $additionalParameters = array());
}