<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
interface IntegerNet_Solr_Implementor_Source
{
    /**
     * @param int $optionId
     * @return string
     */
    public function getOptionText($optionId);
}