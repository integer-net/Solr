<?php
namespace IntegerNet\Solr\Config;
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class ResultsConfig
{
    const SEARCH_OPERATOR_AND = 'AND';
    const SEARCH_OPERATOR_OR = 'OR';

    /**
     * @var bool
     */
    private $useHtmlFromSolr;
    /**
     * @var string {and,or}
     */
    private $searchOperator;
    /**
     * @var float
     */
    private $priorityCategories;
    /**
     * @var float
     */
    private $priceStepSize;
    /**
     * @var float
     */
    private $maxPrice;
    /**
     * @var bool
     */
    private $useCustomPriceIntervals;
    /**
     * @var float[]
     */
    private $customPriceIntervals;
    /**
     * @var bool
     */
    private $showCategoryFilter;

    /**
     * IntegerNet\Solr\Config\ResultsConfig constructor.
     * @param bool $useHtmlFromSolr
     * @param string $searchOperator
     * @param float $priorityCategories
     * @param float $priceStepSize
     * @param float $maxPrice
     * @param bool $useCustomPriceIntervals
     * @param float[] $customPriceIntervals
     * @param bool $showCategoryFilter
     */
    public function __construct($useHtmlFromSolr, $searchOperator, $priorityCategories, $priceStepSize, $maxPrice, $useCustomPriceIntervals, array $customPriceIntervals, $showCategoryFilter)
    {
        $this->useHtmlFromSolr = $useHtmlFromSolr;
        $this->searchOperator = $searchOperator;
        $this->priorityCategories = $priorityCategories;
        $this->priceStepSize = $priceStepSize;
        $this->maxPrice = $maxPrice;
        $this->useCustomPriceIntervals = $useCustomPriceIntervals;
        $this->customPriceIntervals = $customPriceIntervals;
        $this->showCategoryFilter = $showCategoryFilter;
    }

    /**
     * @return boolean
     */
    public function isUseHtmlFromSolr()
    {
        return $this->useHtmlFromSolr;
    }

    /**
     * @return string
     */
    public function getSearchOperator()
    {
        return $this->searchOperator;
    }

    /**
     * @return int
     */
    public function getPriorityCategories()
    {
        return $this->priorityCategories;
    }

    /**
     * @return int
     */
    public function getPriceStepSize()
    {
        return $this->priceStepSize;
    }

    /**
     * @return int
     */
    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    /**
     * @return boolean
     */
    public function isUseCustomPriceIntervals()
    {
        return $this->useCustomPriceIntervals;
    }

    /**
     * @return float[]
     */
    public function getCustomPriceIntervals()
    {
        return $this->customPriceIntervals;
    }

    public function isShowCategoryFilter()
    {
        return $this->showCategoryFilter;
    }
}