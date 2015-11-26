<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Pagination;

class ParamsBuilder
{
    /**
     * @var $attributeRepository AttributeRepository
     */
    protected $attributeRespository;

    /**
     * @var $filterQueryBuilder FilterQueryBuilder
     */
    protected $filterQueryBuilder;
    /**
     * @var $pagination Pagination
     */
    protected $pagination;
    /**
     * @var ResultsConfig
     */
    protected $resultsConfig;

    private $categoryId;
    private $isAutosuggest;

    public function __construct($categoryId, $isAutoSuggest, $attributeRepository, $filterQueryBuilder, $pagination, $resultsConfig)
    {
        $this->categoryId = $categoryId;
        $this->isAutosuggest = $isAutoSuggest;
        $this->attributeRespository = $attributeRepository;
        $this->filterQueryBuilder = $filterQueryBuilder;
        $this->pagination = $pagination;
        $this->resultsConfig = $resultsConfig;
    }

    public function buildAsArray($storeId, $fuzzy)
    {
        $resultsConfig = $this->resultsConfig;
        $params = array(
            'q.op' => $resultsConfig->getSearchOperator(),
            'fq' => $this->getFilterQuery($storeId),
            'fl' => 'result_html_autosuggest_nonindex,score,sku_s,name_s,product_id',
            'sort' => $this->getSortParam(),
            'facet' => 'true',
            'facet.sort' => 'true',
            'facet.mincount' => '1',
            'facet.field' => $this->getFacetFieldCodes(),
            'defType' => 'edismax',
        );

        if (!$this->isAutosuggest()) {
            $params['fl'] = 'result_html_list_nonindex,result_html_grid_nonindex,score,sku_s,name_s,product_id';
            $params['facet.interval'] = 'price_f';
            $params['stats'] = 'true';
            $params['stats.field'] = 'price_f';


            if (($priceStepsize = $resultsConfig->getPriceStepSize())
                && ($maxPrice = $resultsConfig->getMaxPrice())) {
                $params['facet.range'] = 'price_f';
                $params['f.price_f.facet.range.start'] = 0;
                $params['f.price_f.facet.range.end'] = $maxPrice;
                $params['f.price_f.facet.range.gap'] = $priceStepsize;
            }

            if ($resultsConfig->isUseCustomPriceIntervals()
                && ($customPriceIntervals = $resultsConfig->getCustomPriceIntervals())) {
                $params['f.price_f.facet.interval.set'] = array();
                $lowerBorder = 0;
                foreach($customPriceIntervals as $upperBorder) {
                    $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%f]', $lowerBorder, $upperBorder);
                    $lowerBorder = $upperBorder;
                }
                $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%s]', $lowerBorder, '*');
            } else if (($priceStepsize = $resultsConfig->getPriceStepSize())
                && ($maxPrice = $resultsConfig->getMaxPrice())) {
                $params['f.price_f.facet.interval.set'] = array();
                $lowerBorder = 0;
                for ($upperBorder = $priceStepsize; $upperBorder <= $maxPrice; $upperBorder += $priceStepsize) {
                    $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%f]', $lowerBorder, $upperBorder);
                    $lowerBorder = $upperBorder;
                }
                $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%s]', $lowerBorder, '*');
            }
        }

        if (!$fuzzy) {
            $params['mm'] = '0%';
        }

        if ($this->isAutosuggest()) {
            $params['rows'] = $this->pagination->getPageSize();
        }
        return $params;
    }

    /**
     * @return array
     */
    private function getFacetFieldCodes()
    {
        $codes = array('category');

        foreach($this->attributeRespository->getFilterableAttributes() as $attribute) {
            $codes[] = $attribute->getAttributeCode() . '_facet';
        }
        return $codes;
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getFilterQuery($storeId)
    {
        return $this->filterQueryBuilder->buildFilterQuery($storeId);
    }

    /**
     * @return string
     */
    private function getSortParam()
    {
        $sortField = $this->getCurrentSort();
        switch ($sortField) {
            case 'position':
                if ($this->isCategoryPage()) {
                    $param = 'category_' . $this->categoryId . '_position_i';
                } else {
                    $param = 'score';
                }
                break;
            case 'price':
                $param = 'price_f';
                break;
            default:
                $param = $sortField . '_s';
        }

        $param .= ' ' . $this->getCurrentSortDirection();
        return $param;
    }

    /**
     * @return int
     */
    private function getCurrentSortDirection()
    {
        $direction = $this->pagination->getCurrentDirection();

        if ($this->getCurrentSort() == 'position') {
            if (!$this->isCategoryPage()) {
                switch (strtolower($direction)) {
                    case 'desc':
                        return 'asc';
                    default:
                        return 'desc';
                }
            }
        }
        return $direction;
    }

    /**
     * @return string
     */
    private function getCurrentSort()
    {
        return $this->pagination->getCurrentOrder();
    }

    /**
     * @return bool
     */
    private function isCategoryPage()
    {
        return $this->categoryId != null;
    }
    /**
     * @return bool
     */
    private function isAutosuggest()
    {
        return $this->isAutosuggest;
    }

}