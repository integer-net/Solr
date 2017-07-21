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

use IntegerNet\Solr\Config\FuzzyConfig;

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
