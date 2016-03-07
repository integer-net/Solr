<?php
namespace IntegerNet\Solr\Implementor;
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
interface Source
{
    /**
     * @param int $optionId
     * @return string
     */
    public function getOptionText($optionId);

    /**
     * Returns [optionId => optionText] map
     *
     * @return string[]
     */
    public function getOptionMap();
}