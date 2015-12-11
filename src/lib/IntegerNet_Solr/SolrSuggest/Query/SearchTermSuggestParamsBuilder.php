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

use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Query\SearchString;

class SearchTermSuggestParamsBuilder implements ParamsBuilder
{
    /**
     * @var AutosuggestConfig
     */
    private $autosuggestConfig;
    /**
     * @var int
     */
    private $storeId;
    /**
     * @var SearchString
     */
    private $searchString;

    /**
     * SearchTermSuggestParamsBuilder constructor.
     * @param SearchString $searchString
     * @param AutosuggestConfig $autosuggestConfig
     * @param int $storeId
     */
    public function __construct(SearchString $searchString, AutosuggestConfig $autosuggestConfig, $storeId)
    {
        $this->autosuggestConfig = $autosuggestConfig;
        $this->storeId = $storeId;
        $this->searchString = $searchString;
    }

    /**
     * Return parameters as array as expected by solr service
     *
     * @return mixed[]
     */
    public function buildAsArray()
    {
        $params = array(
            'fq' => 'store_id:' . $this->getStoreId(),
            'df' => 'text_autocomplete',
            'facet' => 'true',
            'facet.field' => 'text_autocomplete',
            'facet.sort' => 'count',
            'facet.limit' => $this->autosuggestConfig->getMaxNumberSearchwordSuggestions(),
            'f.text_autocomplete.facet.prefix' => strtolower($this->searchString->getEscapedString()),
        );
        return $params;
    }

    /**
     * Return store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

}