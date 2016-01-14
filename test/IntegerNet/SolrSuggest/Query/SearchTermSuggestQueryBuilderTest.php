<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Query;

use IntegerNet\Solr\Config\Stub\AutosuggestConfigBuilder;
use PHPUnit_Framework_TestCase;
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Query\SearchString;
use IntegerNet\Solr\Query\Query;

class SearchTermSuggestQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider dataQueryBuilder
     * @param $storeId
     * @param AutosuggestConfig $autosuggestConfig
     * @param SearchString $searchString
     * @param Query $expectedQuery
     */
    public function testQuery($storeId, AutosuggestConfig $autosuggestConfig,
                              SearchString $searchString, Query $expectedQuery)
    {
        $paramsBuilder = new SearchTermSuggestParamsBuilder($searchString, $autosuggestConfig, $storeId);
        $searchQueryBuilder = new SearchTermSuggestQueryBuilder($paramsBuilder, $storeId);
        $actualQuery = $searchQueryBuilder->build();
        $this->assertEquals($expectedQuery, $actualQuery);
    }
    public static function dataQueryBuilder()
    {
        $defaultStoreId = 0;
        $defaultExpectedParams = [
            'fq' => 'store_id:' . $defaultStoreId,
            'df' => 'text_autocomplete',
            'facet' => 'true',
            'facet.field' => 'text_autocomplete',
            'facet.sort' => 'count',
            'facet.limit' => AutosuggestConfigBuilder::MAX_NUMBER_SEARCHWORD_SUGGESTIONS,
            'f.text_autocomplete.facet.prefix' => 'foo bar',
        ];
        $defaultAutosuggestConfig = AutosuggestConfigBuilder::defaultConfig()->build();
        $allData = [
            'default' => [$defaultStoreId, $defaultAutosuggestConfig, new SearchString('Foo Bar'),
                new Query($defaultStoreId, '*', 0, 0, $defaultExpectedParams)
            ],

        ];
        foreach ($allData as $parameters) {
            yield $parameters;
        }
    }
}
