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
use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Pagination;

abstract class AbstractParamsBuilder implements ParamsBuilder
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
     * @var $resultsConfig ResultsConfig
     */
    protected $resultsConfig;
    /**
     * @var $fuzzyConfig FuzzyConfig
     */
    protected $fuzzyConfig;
    /**
     * @var $storeId int
     */
    private $storeId;

    public function __construct(AttributeRepository $attributeRepository, FilterQueryBuilder $filterQueryBuilder,
                                Pagination $pagination, ResultsConfig $resultsConfig, FuzzyConfig $fuzzyConfig, $storeId)
    {
        $this->attributeRespository = $attributeRepository;
        $this->filterQueryBuilder = $filterQueryBuilder;
        $this->pagination = $pagination;
        $this->resultsConfig = $resultsConfig;
        $this->fuzzyConfig = $fuzzyConfig;
        $this->storeId = (int) $storeId;
    }

    /**
     * @return FilterQueryBuilder
     */
    public function getFilterQueryBuilder()
    {
        return $this->filterQueryBuilder;
    }

    public function buildAsArray()
    {
        $params = array(
            'q.op' => $this->resultsConfig->getSearchOperator(),
            'fq' => $this->getFilterQuery(),
            'fl' => 'result_html_autosuggest_nonindex,score,sku_s,name_s,product_id',
            'sort' => $this->getSortParam(),
            'facet' => 'true',
            'facet.sort' => 'true',
            'facet.mincount' => '1',
            'facet.field' => $this->getFacetFieldCodes(),
            'defType' => 'edismax',
        );

        $params = $this->addFacetParams($params);

        if (!$this->fuzzyConfig->isActive()) {
            $params['mm'] = '0%';
        }
        return $params;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->pagination->getCurrentPage();
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pagination->getPageSize();
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
     * @return string
     */
    private function getFilterQuery()
    {
        return $this->filterQueryBuilder->buildFilterQuery($this->getStoreId());
    }

    /**
     * @return string
     */
    private function getSortParam()
    {
        return $this->getCurrentSortField() . ' ' . $this->getCurrentSortDirection();
    }

    /**
     * @return int
     */
    protected function getCurrentSortDirection()
    {
        $direction = $this->pagination->getCurrentDirection();

        if ($this->getCurrentSortField() == 'score') {
            switch (strtolower($direction)) {
                case 'desc':
                    return 'asc';
                default:
                    return 'desc';
            }
        }
        return $direction;
    }

    /**
     * @return string
     */
    protected function getCurrentSortField()
    {
        $sortField = $this->pagination->getCurrentOrder();
        switch ($sortField) {
            case 'position':
                $sortFieldForSolr = 'score';
                break;
            case 'price':
                $sortFieldForSolr = 'price_f';
                break;
            default:
                $sortFieldForSolr = $sortField . '_s';
        }
        return $sortFieldForSolr;
    }

    /**
     * @return bool
     */
    private function isCategoryPage()
    {
        return $this->categoryId != null;
    }

    /**
     * @param mixed[] $params
     * @return mixed[]
     */
    protected function addFacetParams($params)
    {
        $resultsConfig = $this->resultsConfig;

        $params['fl'] = 'result_html_list_nonindex,result_html_grid_nonindex,score,sku_s,name_s,product_id';
        $params['facet.interval'] = 'price_f';
        $params['stats'] = 'true';
        $params['stats.field'] = 'price_f';


        if (($priceStepsize = $resultsConfig->getPriceStepSize())
            && ($maxPrice = $resultsConfig->getMaxPrice())
        ) {
            $params['facet.range'] = 'price_f';
            $params['f.price_f.facet.range.start'] = 0;
            $params['f.price_f.facet.range.end'] = $maxPrice;
            $params['f.price_f.facet.range.gap'] = $priceStepsize;
        }

        if ($resultsConfig->isUseCustomPriceIntervals()
            && ($customPriceIntervals = $resultsConfig->getCustomPriceIntervals())
        ) {
            $params['f.price_f.facet.interval.set'] = array();
            $lowerBorder = 0;
            foreach ($customPriceIntervals as $upperBorder) {
                $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%f]', $lowerBorder, $upperBorder);
                $lowerBorder = $upperBorder;
            }
            $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%s]', $lowerBorder, '*');
            return $params;
        } else if (($priceStepsize = $resultsConfig->getPriceStepSize())
            && ($maxPrice = $resultsConfig->getMaxPrice())
        ) {
            $params['f.price_f.facet.interval.set'] = array();
            $lowerBorder = 0;
            for ($upperBorder = $priceStepsize; $upperBorder <= $maxPrice; $upperBorder += $priceStepsize) {
                $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%f]', $lowerBorder, $upperBorder);
                $lowerBorder = $upperBorder;
            }
            $params['f.price_f.facet.interval.set'][] = sprintf('(%f,%s]', $lowerBorder, '*');
            return $params;
        }return $params;
    }

}