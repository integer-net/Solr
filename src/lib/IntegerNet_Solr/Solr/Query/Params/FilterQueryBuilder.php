<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query\Params;

use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Implementor\Attribute;

class FilterQueryBuilder
{
    /**
     * @var $resultsConfig ResultsConfig
     */
    private $resultsConfig;
    /**
     * @var $isCategoryPage bool
     */
    private $isCategoryPage = false;
    /**
     * @var $filters array
     */
    private $filters = array();

    /**
     * @param ResultsConfig $resultsConfig
     */
    public function __construct(ResultsConfig $resultsConfig)
    {
        $this->resultsConfig = $resultsConfig;
    }

    public static function noFilterQueryBuilder(ResultsConfig $resultsConfig)
    {
        return new self($resultsConfig);
    }

    /**
     * @return ResultsConfig
     */
    private function getResultsConfig()
    {
        return $this->resultsConfig;
    }


    /**
     * @param $isCategoryPage
     * @return $this
     */
    public function setIsCategoryPage($isCategoryPage)
    {
        $this->isCategoryPage = $isCategoryPage;
        return $this;
    }

    /**
     * @param Attribute $attribute
     * @param $value
     * @return $this
     */
    public function addAttributeFilter(Attribute $attribute, $value)
    {
        $this->filters[$attribute->getAttributeCode() . '_facet'] = $value;
        return $this;
    }


    /**
     * @param int $categoryId
     * @return $this
     */
    public function addCategoryFilter($categoryId)
    {
        $this->filters['category'] = $categoryId;
        return $this;
    }

    /**
     * @param int $range
     * @param int $index
     */
    public function addPriceRangeFilterByConfiguration($range, $index)
    {
        if ($this->getResultsConfig()->isUseCustomPriceIntervals()
            && $customPriceIntervals = $this->getResultsConfig()->getCustomPriceIntervals()
        ) {
            $this->addPriceRangeFilterWithCustomIntervals($index, $customPriceIntervals);
        } else {
            $this->addPriceRangeFilter($range, $index);
        }
    }

    /**
     * @param $range
     * @param $index
     * @return $this
     */
    public function addPriceRangeFilter($range, $index)
    {
        $maxPrice = $index * $range;
        $minPrice = $maxPrice - $range;
        $this->filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
        return $this;
    }

    /**
     * @param $index
     * @param $customPriceIntervals
     * @return $this
     */
    public function addPriceRangeFilterWithCustomIntervals($index, $customPriceIntervals)
    {
        $lowerBorder = 0;
        $i = 1;
        foreach (explode(',', $customPriceIntervals) as $upperBorder) {
            if ($i == $index) {
                $this->filters['price_f'] = sprintf('[%f TO %f]', $lowerBorder, $upperBorder);
                return;
            }
            $i++;
            $lowerBorder = $upperBorder;
            continue;
        }
        $this->filters['price_f'] = sprintf('[%f TO %s]', $lowerBorder, '*');
        return $this;
    }

    /**
     * @param float $minPrice
     * @param float $maxPrice
     * @return $this
     */
    public function addPriceRangeFilterByMinMax($minPrice, $maxPrice = 0.0)
    {
        if ($maxPrice) {
            $this->filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
        } else {
            $this->filters['price_f'] = sprintf('[%f TO *]', $minPrice);
        }
        return $this;
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function buildFilterQuery($storeId)
    {
            $filterQuery = 'store_id:' . $storeId;
            if ($this->isCategoryPage) {
                $filterQuery .= ' AND is_visible_in_catalog_i:1';
            } else {
                $filterQuery .= ' AND is_visible_in_search_i:1';
            }

            foreach($this->filters as $attributeCode => $value) {
                if (is_array($value)) {
                    $filterQuery .= ' AND (';
                    $filterQueryParts = array();
                    foreach($value as $singleValue) {
                        $filterQueryParts[] = $attributeCode . ':' . $singleValue;
                    }
                    $filterQuery .= implode(' OR ', $filterQueryParts);
                    $filterQuery .= ')';
                } else {
                    $filterQuery .= ' AND ' . $attributeCode . ':' . $value;
                }
            }

        return $filterQuery;
    }

}