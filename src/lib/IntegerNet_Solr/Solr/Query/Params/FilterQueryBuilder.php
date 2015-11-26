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

use IntegerNet\Solr\Implementor\Attribute;

class FilterQueryBuilder
{
    /**
     * @var $_isCategoryPage bool
     */
    private $_isCategoryPage = false;
    /**
     * @var $_filters array
     */
    private $_filters = array();

    public function __construct()
    {

    }

    /**
     * @param $isCategoryPage
     */
    public function setIsCategoryPage($isCategoryPage)
    {
        $this->_isCategoryPage = $isCategoryPage;
    }

    /**
     * @param Attribute $attribute
     * @param $value
     */
    public function addAttributeFilter(Attribute $attribute, $value)
    {
        $this->_filters[$attribute->getAttributeCode() . '_facet'] = $value;
    }


    /**
     * @param int $categoryId
     */
    public function addCategoryFilter($categoryId)
    {
        $this->_filters['category'] = $categoryId;
    }

    /**
     * @param $range
     * @param $index
     */
    public function addPriceRangeFilter($range, $index)
    {
        $maxPrice = $index * $range;
        $minPrice = $maxPrice - $range;
        $this->_filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
    }

    /**
     * @param $index
     * @param $customPriceIntervals
     */
    public function addPriceRangeFilterWithCustomIntervals($index, $customPriceIntervals)
    {
        $lowerBorder = 0;
        $i = 1;
        foreach (explode(',', $customPriceIntervals) as $upperBorder) {
            if ($i == $index) {
                $this->_filters['price_f'] = sprintf('[%f TO %f]', $lowerBorder, $upperBorder);
                return;
            }
            $i++;
            $lowerBorder = $upperBorder;
            continue;
        }
        $this->_filters['price_f'] = sprintf('[%f TO %s]', $lowerBorder, '*');
    }

    /**
     * @param float $minPrice
     * @param float $maxPrice
     */
    public function addPriceRangeFilterByMinMax($minPrice, $maxPrice = 0.0)
    {
        if ($maxPrice) {
            $this->_filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
        } else {
            $this->_filters['price_f'] = sprintf('[%f TO *]', $minPrice);
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function buildFilterQuery($storeId)
    {
            $filterQuery = 'store_id:' . $storeId;
            if ($this->_isCategoryPage) {
                $filterQuery .= ' AND is_visible_in_catalog_i:1';
            } else {
                $filterQuery .= ' AND is_visible_in_search_i:1';
            }

            foreach($this->_filters as $attributeCode => $value) {
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