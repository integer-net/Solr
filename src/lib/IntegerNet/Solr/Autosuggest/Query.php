<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

/**
 * This class is a low weight replacement for the "Mage_Core_Model_Store" class in autosuggest calls
 *
 * Class IntegerNet_Solr_Autosuggest_Query
 */
final class IntegerNet_Solr_Autosuggest_Query
{
    public function getQueryText()
    {
        return $_GET['q'];
    }

    public function getSynonymFor()
    {
        return false;
    }
}