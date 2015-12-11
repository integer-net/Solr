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

class AutosuggestConfigBuilder
{
    const MAX_NUMBER_SEARCHWORD_SUGGESTIONS = 3;

    private $active = true;
    private $usePhpFile = 0;
    private $maxNumberSearchWordSuggestions = self::MAX_NUMBER_SEARCHWORD_SUGGESTIONS;
    private $maxNumberProductSuggestions = 5;
    private $maxNumberCategorySuggestions = 8;
    private $showCompleteCategoryPath = 0;
    private $categoryLinkType = 'filter';
    private $attributeFilterSuggestions = array();

    public static function defaultConfig()
    {
        return new static;
    }

    /**
     * @param boolean $active
     * @return AutosuggestConfigBuilder
     */
    public function withActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param int $usePhpFile
     * @return AutosuggestConfigBuilder
     */
    public function withUsePhpFile($usePhpFile)
    {
        $this->usePhpFile = $usePhpFile;
        return $this;
    }

    /**
     * @param int $maxNumberSearchWordSuggesions
     * @return AutosuggestConfigBuilder
     */
    public function withMaxNumberSearchWordSuggesions($maxNumberSearchWordSuggesions)
    {
        $this->maxNumberSearchWordSuggestions = $maxNumberSearchWordSuggesions;
        return $this;
    }

    /**
     * @param int $maxNumberProductSuggesions
     * @return AutosuggestConfigBuilder
     */
    public function withMaxNumberProductSuggesions($maxNumberProductSuggesions)
    {
        $this->maxNumberProductSuggestions = $maxNumberProductSuggesions;
        return $this;
    }

    /**
     * @param int $maxNumberCategorySuggestions
     * @return AutosuggestConfigBuilder
     */
    public function withMaxNumberCategorySuggestions($maxNumberCategorySuggestions)
    {
        $this->maxNumberCategorySuggestions = $maxNumberCategorySuggestions;
        return $this;
    }

    /**
     * @param int $showCompleteCategoryPath
     * @return AutosuggestConfigBuilder
     */
    public function withShowCompleteCategoryPath($showCompleteCategoryPath)
    {
        $this->showCompleteCategoryPath = $showCompleteCategoryPath;
        return $this;
    }

    /**
     * @param string $categoryLinkType
     * @return AutosuggestConfigBuilder
     */
    public function withCategoryLinkType($categoryLinkType)
    {
        $this->categoryLinkType = $categoryLinkType;
        return $this;
    }

    /**
     * @param array $attributeFilterSuggestions
     * @return AutosuggestConfigBuilder
     */
    public function withAttributeFilterSuggestions($attributeFilterSuggestions)
    {
        $this->attributeFilterSuggestions = $attributeFilterSuggestions;
        return $this;
    }



    public function build()
    {
        return new AutosuggestConfig($this->active, $this->usePhpFile, $this->maxNumberSearchWordSuggestions,
            $this->maxNumberProductSuggestions, $this->maxNumberCategorySuggestions, $this->showCompleteCategoryPath,
            $this->categoryLinkType, $this->attributeFilterSuggestions);
    }
}