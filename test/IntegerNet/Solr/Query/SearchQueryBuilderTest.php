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
use IntegerNet\Solr\Implementor\Source;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use Mage_Catalog_Model_Entity_Attribute;
use Mage_Catalog_Model_Resource_Product_Attribute_Collection;
use PHPUnit_Framework_TestCase;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Pagination;
use BadMethodCallException;
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
        $eventDispatcherMock = $this->getMockForAbstractClass(EventDispatcher::class);
        $eventDispatcherMock->expects($this->once())->method('dispatch')->with(
            'integernet_solr_update_query_text', $this->equalTo(
                ['transport' => new Transport(['query_text' => $searchString->getRawString()])]
            )
        );
        $attributeRepositoryStub = new AttributeRepositoryStub();
        $paramsBuilder = new SearchParamsBuilder($attributeRepositoryStub, $filterQueryBuilder, $paginationStub,
            $resultsConfig, $fuzzyConfig, $storeId);
        $searchQueryBuilder = new SearchQueryBuilder($searchString, $fuzzyConfig, $attributeRepositoryStub, $paginationStub,
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
                new Query(1, 'attribute1_t:""foo bar""~100^0 attribute2_t:""foo bar""~100^0', 0, 24, [
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
class ResultConfigBuilder
{
    const DEFAULT_MAX_PRICE = 50;
    const DEFAULT_STEP_SIZE = 10;
    /**
     * @var bool
     */
    private $useHtmlFromSolr = false;
    /**
     * @var string {and,or}
     */
    private $searchOperator = ResultsConfig::SEARCH_OPERATOR_AND;
    /**
     * @var float
     */
    private $priceStepSize = self::DEFAULT_STEP_SIZE;
    /**
     * @var float
     */
    private $maxPrice = self::DEFAULT_MAX_PRICE;
    /**
     * @var bool
     */
    private $useCustomPriceIntervals = false;
    /**
     * @var float[]
     */
    private $customPriceIntervals = [10,20,50,100,200,300,400,500];

    public static function defaultConfig()
    {
        return new static;
    }
    public static function alternativeConfig()
    {
        return self::defaultConfig()
            ->withUseHtmlFromSolr(true)
            ->withSearchOperator(ResultsConfig::SEARCH_OPERATOR_OR)
            ->withUseCustomPriceIntervals(true);
    }

    /**
     * @param boolean $useHtmlFromSolr
     * @return $this
     */
    public function withUseHtmlFromSolr($useHtmlFromSolr)
    {
        $this->useHtmlFromSolr = $useHtmlFromSolr;
        return $this;
    }

    /**
     * @param string $searchOperator
     * @return $this
     */
    public function withSearchOperator($searchOperator)
    {
        $this->searchOperator = $searchOperator;
        return $this;
    }

    /**
     * @param float $priceStepSize
     * @return $this
     */
    public function withPriceStepSize($priceStepSize)
    {
        $this->priceStepSize = $priceStepSize;
        return $this;
    }

    /**
     * @param float $maxPrice
     * @return $this
     */
    public function withMaxPrice($maxPrice)
    {
        $this->maxPrice = $maxPrice;
        return $this;
    }

    /**
     * @param boolean $useCustomPriceIntervals
     * @return $this
     */
    public function withUseCustomPriceIntervals($useCustomPriceIntervals)
    {
        $this->useCustomPriceIntervals = $useCustomPriceIntervals;
        return $this;
    }

    /**
     * @param \float[] $customPriceIntervals
     * @return $this
     */
    public function withCustomPriceIntervals($customPriceIntervals)
    {
        $this->customPriceIntervals = $customPriceIntervals;
        return $this;
    }

    public function build()
    {
        return new ResultsConfig($this->useCustomPriceIntervals, $this->searchOperator, $this->priceStepSize,
            $this->maxPrice, $this->useCustomPriceIntervals, $this->customPriceIntervals);
    }
}
class FuzzyConfigBuilder
{
    /**
     * @var bool
     */
    private $active = true;
    /**
     * @var float
     */
    private $sensitivity = .7;
    /**
     * @var int
     */
    private $minimumResults = 0;

    public static function defaultConfig()
    {
        return new static;
    }
    public static function inactiveConfig()
    {
        return self::defaultConfig()->withActive(false);
    }
    public function build()
    {
        return new FuzzyConfig($this->active, $this->sensitivity, $this->minimumResults);
    }

    /**
     * @param boolean $active
     * @return FuzzyConfigBuilder
     */
    public function withActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param float $sensitivity
     * @return FuzzyConfigBuilder
     */
    public function withSensitivity($sensitivity)
    {
        $this->sensitivity = $sensitivity;
        return $this;
    }

    /**
     * @param int $minimumResults
     * @return FuzzyConfigBuilder
     */
    public function withMinimumResults($minimumResults)
    {
        $this->minimumResults = $minimumResults;
        return $this;
    }

}
class PaginationStub implements Pagination
{
    const DEFAULT_PAGESIZE = 10;
    const DEFAULT_CURRENT = 1;
    /** @var int */
    private $pageSize;
    /** @var  int */
    private $currentPage;
    /** @var  string */
    private $currentDirection;
    /** @var  string */
    private $currentOrder;

    /**
     * PaginationStub constructor.
     * @param int $pageSize
     * @param int $currentPage
     * @param string $currentDirection
     * @param string $currentOrder
     */
    public function __construct($pageSize, $currentPage, $currentDirection, $currentOrder)
    {
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;
        $this->currentDirection = $currentDirection;
        $this->currentOrder = $currentOrder;
    }

    public static function defaultPagination()
    {
        return new self(self::DEFAULT_PAGESIZE, self::DEFAULT_CURRENT, 'ASC', 'position');
    }
    public static function alternativePagination()
    {
        return new self(12, 2, 'DESC', 'attribute1');
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }

    /**
     * @return string
     */
    public function getCurrentDirection()
    {
        return $this->currentDirection;
    }

    /**
     * @param string $currentDirection
     */
    public function setCurrentDirection($currentDirection)
    {
        $this->currentDirection = $currentDirection;
    }

    /**
     * @return string
     */
    public function getCurrentOrder()
    {
        return $this->currentOrder;
    }

    /**
     * @param string $currentOrder
     */
    public function setCurrentOrder($currentOrder)
    {
        $this->currentOrder = $currentOrder;
    }

}

class AttributeRepositoryStub implements AttributeRepository
{
    /**
     * @todo convert to IntegerNet\Solr\Implementor\Attribute array, maybe add getSearchableAttributeCodes()
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getSearchableAttributes()
    {
        return [AttributeStub::sortableString('attribute1'), AttributeStub::sortableString('attribute2')];
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        return [AttributeStub::sortableString('attribute1'), AttributeStub::sortableString('attribute2')];
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true)
    {
        throw new BadMethodCallException('not used in query builder');
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true)
    {
        throw new BadMethodCallException('not used in query builder');
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
    {
        throw new BadMethodCallException('not used in query builder');
    }

    /**
     * @return string[]
     */
    public function getAttributeCodesToIndex()
    {
        throw new BadMethodCallException('not used in query builder');
    }

}
class AttributeStub implements Attribute
{
    /** @var  string */
    private $attributeCode;
    /** @var  string */
    private $storeLabel;
    /** @var  float */
    private $solrBoost;
    /** @var  Source */
    private $source;
    /** @var  string */
    private $backendType;
    /** @var  bool */
    private $isSearchable;
    /** @var  bool */
    private $usedForSortBy;

    public static function sortableString($name)
    {
        return new self($name, $name, 0, null, 'string', true, true);
    }

    public function __construct($attributeCode, $storeLabel, $solrBoost, Source $source = null, $backendType, $isSearchable, $usedForSortBy)
    {
        $this->attributeCode = $attributeCode;
        $this->storeLabel = $storeLabel;
        $this->solrBoost = $solrBoost;
        $this->source = $source;
        $this->backendType = $backendType;
        $this->isSearchable = $isSearchable;
        $this->usedForSortBy = $usedForSortBy;
    }

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    /**
     * @param string $attributeCode
     */
    public function setAttributeCode($attributeCode)
    {
        $this->attributeCode = $attributeCode;
    }

    /**
     * @return string
     */
    public function getStoreLabel()
    {
        return $this->storeLabel;
    }

    /**
     * @param string $storeLabel
     */
    public function setStoreLabel($storeLabel)
    {
        $this->storeLabel = $storeLabel;
    }

    /**
     * @return float
     */
    public function getSolrBoost()
    {
        return $this->solrBoost;
    }

    /**
     * @param float $solrBoost
     */
    public function setSolrBoost($solrBoost)
    {
        $this->solrBoost = $solrBoost;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param Source $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getBackendType()
    {
        return $this->backendType;
    }

    /**
     * @param string $backendType
     */
    public function setBackendType($backendType)
    {
        $this->backendType = $backendType;
    }

    /**
     * @return boolean
     */
    public function getIsSearchable()
    {
        return $this->isSearchable;
    }

    /**
     * @param boolean $isSearchable
     */
    public function setIsSearchable($isSearchable)
    {
        $this->isSearchable = $isSearchable;
    }

    /**
     * @return boolean
     */
    public function getUsedForSortBy()
    {
        return $this->usedForSortBy;
    }

    /**
     * @param boolean $usedForSortBy
     */
    public function setUsedForSortBy($usedForSortBy)
    {
        $this->usedForSortBy = $usedForSortBy;
    }

}