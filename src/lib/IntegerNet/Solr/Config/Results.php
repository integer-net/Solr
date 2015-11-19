<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class IntegerNet_Solr_Config_Results
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
     * IntegerNet_Solr_Config_Results constructor.
     * @param bool $useHtmlFromSolr
     * @param string $searchOperator
     * @param float $priceStepSize
     * @param float $maxPrice
     * @param bool $useCustomPriceIntervals
     * @param float[] $customPriceIntervals
     */
    public function __construct($useHtmlFromSolr, $searchOperator, $priceStepSize, $maxPrice, $useCustomPriceIntervals, array $customPriceIntervals)
    {
        $this->useHtmlFromSolr = $useHtmlFromSolr;
        $this->searchOperator = $searchOperator;
        $this->priceStepSize = $priceStepSize;
        $this->maxPrice = $maxPrice;
        $this->useCustomPriceIntervals = $useCustomPriceIntervals;
        $this->customPriceIntervals = $customPriceIntervals;
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


}