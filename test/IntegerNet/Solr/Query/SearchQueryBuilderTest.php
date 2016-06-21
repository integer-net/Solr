<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;
use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\Stub\AttributeRepositoryStub;
use IntegerNet\Solr\Implementor\Stub\AttributeStub;
use IntegerNet\Solr\Implementor\Stub\PaginationStub;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Config\Stub\FuzzyConfigBuilder;
use IntegerNet\Solr\Config\Stub\ResultConfigBuilder;
use PHPUnit_Framework_TestCase;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Implementor\EventDispatcher;

class SearchQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider dataQueryBuilder
     * @param $storeId
     * @param ResultsConfig $resultsConfig
     * @param FuzzyConfig $fuzzyConfig
     * @param FilterQueryBuilder $filterQueryBuilder
     * @param Pagination $paginationStub
     * @param SearchString $searchString
     * @param Query $expectedQuery
     */
    public function testQuery($storeId, ResultsConfig $resultsConfig, FuzzyConfig $fuzzyConfig,
                              FilterQueryBuilder $filterQueryBuilder, Pagination $paginationStub,
                              SearchString $searchString, Query $expectedQuery)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcher $eventDispatcherMock */
        $eventDispatcherMock = $this->getMockForAbstractClass(EventDispatcher::class);
        $eventDispatcherMock->expects($this->at(0))->method('dispatch')->with(
            'integernet_solr_update_query_text',
            $this->equalTo(
                ['transport' => new Transport(['query_text' => $searchString->getRawString()])]
            )
        );
//        $eventDispatcherMock->expects($this->at(1))->method('dispatch')->with(
//            'integernet_solr_get_fieldname',
//            $this->anything()
//        );
        $attributeRepositoryStub = new AttributeRepositoryStub();
        $paramsBuilder = new SearchParamsBuilder($attributeRepositoryStub, $filterQueryBuilder, $paginationStub,
            $resultsConfig, $fuzzyConfig, $storeId, $eventDispatcherMock);
        $searchQueryBuilder = new SearchQueryBuilder($searchString, $fuzzyConfig, $resultsConfig, $attributeRepositoryStub, $paginationStub,
            $paramsBuilder, $storeId, $eventDispatcherMock);
        $actualQuery = $searchQueryBuilder->build();
        $this->assertEquals($expectedQuery, $actualQuery);
    }
    public static function dataQueryBuilder()
    {
        $defaultStoreId = 0;
        $defaultPagination = PaginationStub::defaultPagination();
        $defaultExpectedParams = [
            'q.op' => ResultsConfig::SEARCH_OPERATOR_AND,
            'fq' => "store_id:$defaultStoreId AND is_visible_in_search_i:1",
            'fl' => 'result_html_list_nonindex,result_html_grid_nonindex,score,sku_s,name_s,product_id',
            'sort' => 'score desc',
            'facet' => 'true',
            'facet.sort' => 'true',
            'facet.mincount' => '1',
            'facet.field' => ['category', 'attribute1_facet', 'attribute2_facet'],
            'defType' => 'edismax',
            'facet.interval' => 'price_f',
            'stats' => 'true',
            'stats.field' => 'price_f',
            'facet.range' => 'price_f',
            'f.price_f.facet.range.start' => 0,
            'f.price_f.facet.range.end' => ResultConfigBuilder::DEFAULT_MAX_PRICE,
            'f.price_f.facet.range.gap' => ResultConfigBuilder::DEFAULT_STEP_SIZE,
            'f.price_f.facet.interval.set' => [
                "(0.000000,10.000000]", "(10.000000,20.000000]", "(20.000000,30.000000]", "(30.000000,40.000000]", "(40.000000,50.000000]", "(50.000000,*]"
            ]
        ];
        $defaultResultConfig = ResultConfigBuilder::defaultConfig()->build();
        $allData = [
            'default' => [$defaultStoreId, $defaultResultConfig, FuzzyConfigBuilder::defaultConfig()->build(),
                FilterQueryBuilder::noFilterQueryBuilder($defaultResultConfig), $defaultPagination, new SearchString('foo bar'),
                new Query($defaultStoreId, 'foo bar~0.7', 0, PaginationStub::DEFAULT_PAGESIZE, $defaultExpectedParams)
            ],
            'alternative' => [1, ResultConfigBuilder::alternativeConfig()->build(), FuzzyConfigBuilder::inactiveConfig()->build(),
                FilterQueryBuilder::noFilterQueryBuilder($defaultResultConfig), PaginationStub::alternativePagination(), new SearchString('"foo bar"'),
                new Query(1, 'attribute1_t:""foo bar""~100^0 OR attribute2_t:""foo bar""~100^0 OR category_name_t_mv:""foo bar""~100^1', 0, 24, [
                        'q.op' => ResultsConfig::SEARCH_OPERATOR_OR,
                        'fq' => "store_id:1 AND is_visible_in_search_i:1",
                        'sort' => 'attribute1_s desc',
                        'f.price_f.facet.interval.set' => [
                            "(0.000000,10.000000]", "(10.000000,20.000000]", "(20.000000,50.000000]", "(50.000000,100.000000]", "(100.000000,200.000000]",
                            "(200.000000,300.000000]", "(300.000000,400.000000]", "(400.000000,500.000000]", "(500.000000,*]",
                        ],
                        'mm' => '0%'
                    ] + $defaultExpectedParams)
            ],
            'filters' => [$defaultStoreId, $defaultResultConfig, FuzzyConfigBuilder::defaultConfig()->build(),
                FilterQueryBuilder::noFilterQueryBuilder($defaultResultConfig)
                    ->addAttributeFilter(AttributeStub::sortableString('attribute1'), 'blue')
                    ->addCategoryFilter(42)
                    ->addPriceRangeFilterByMinMax(13,37),
                $defaultPagination, new SearchString('foo bar'),
                new Query($defaultStoreId, 'foo bar~0.7', 0, PaginationStub::DEFAULT_PAGESIZE, [
                        'fq' => 'store_id:0 AND is_visible_in_search_i:1 AND attribute1_facet:blue AND category:42 AND price_f:[13.000000 TO 37.000000]'
                    ] + $defaultExpectedParams)
            ]
        ];
        foreach ($allData as $parameters) {
            yield $parameters;
        }
    }
}
