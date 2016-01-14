<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Config\Stub;

use IntegerNet\Solr\Config\ResultsConfig;

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
