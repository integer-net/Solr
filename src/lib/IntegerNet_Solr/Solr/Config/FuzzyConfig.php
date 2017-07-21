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
final class FuzzyConfig
{
    /**
     * @var bool
     */
    private $active;
    /**
     * @var float
     */
    private $sensitivity;
    /**
     * @var int
     */
    private $minimumResults;

    /**
     * @param bool $active
     * @param float $sensitivity
     * @param int $minimumResults
     */
    public function __construct($active, $sensitivity, $minimumResults)
    {
        $this->active = $active;
        $this->sensitivity = $sensitivity;
        $this->minimumResults = $minimumResults;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return float
     */
    public function getSensitivity()
    {
        return $this->sensitivity;
    }

    /**
     * @return int
     */
    public function getMinimumResults()
    {
        return $this->minimumResults;
    }


}