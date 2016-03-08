<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Query;

use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Query\ParamsBuilder;
use IntegerNet\Solr\Query\SearchString;

class CategorySuggestParamsBuilder implements ParamsBuilder
{
    /**
     * @var AutosuggestConfig
     */
    private $autosuggestConfig;
    /**
     * @var ResultsConfig
     */
    private $resultsConfig;
    /**
     * @var int
     */
    private $storeId;
    /**
     * @var SearchString
     */
    private $searchString;

    /**
     * CategorySuggestParamsBuilder constructor.
     * @param SearchString $searchString
     * @param AutosuggestConfig $autosuggestConfig
     * @param ResultsConfig $resultsConfig
     * @param int $storeId
     */
    public function __construct(SearchString $searchString, AutosuggestConfig $autosuggestConfig, ResultsConfig $resultsConfig, $storeId)
    {
        $this->resultsConfig = $resultsConfig;
        $this->autosuggestConfig = $autosuggestConfig;
        $this->storeId = $storeId;
        $this->searchString = $searchString;
    }

    /**
     * Return parameters as array as expected by solr service
     *
     * @param string $attributeToReset
     * @return mixed[]
     */
    public function buildAsArray($attributeToReset = '')
    {
        $params = array(
            'q.op' => $this->resultsConfig->getSearchOperator(),
            'fq' => 'content_type:category AND store_id:' . $this->storeId,
            'fl' => 'name_t, url_s_nonindex',
            'sort' => 'score desc',
            'defType' => 'edismax',
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