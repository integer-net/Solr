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

    public static function noFilterQueryBuilder()
    {
        return new self;
    }

    /**
     * @param $isCategoryPage
     * @return $this
     */
    public function setIsCategoryPage($isCategoryPage)
    {
        $this->_isCategoryPage = $isCategoryPage;
        return $this;
    }

    /**
     * @param Attribute $attribute
     * @param $value
     * @return $this
     */
    public function addAttributeFilter(Attribute $attribute, $value)
    {
        $this->_filters[$attribute->getAttributeCode() . '_facet'] = $value;
        return $this;
    }


    /**
     * @param int $categoryId
     * @return $this
     */
    public function addCategoryFilter($categoryId)
    {
        $this->_filters['category'] = $categoryId;
        return $this;
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
        $this->_filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
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
                $this->_filters['price_f'] = sprintf('[%f TO %f]', $lowerBorder, $upperBorder);
                return;
            }
            $i++;
            $lowerBorder = $upperBorder;
            continue;
        }
        $this->_filters['price_f'] = sprintf('[%f TO %s]', $lowerBorder, '*');
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
            $this->_filters['price_f'] = sprintf('[%f TO %f]', $minPrice, $maxPrice);
        } else {
            $this->_filters['price_f'] = sprintf('[%f TO *]', $minPrice);
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