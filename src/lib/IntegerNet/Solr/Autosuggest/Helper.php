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
 * Class IntegerNet_Solr_Autosuggest_Helper
 */
final class IntegerNet_Solr_Autosuggest_Helper
{
    protected $_query;

    public function getQuery()
    {
        if (is_null($this->_query)) {
            require_once('lib' . DS . 'IntegerNet' . DS . 'Solr' . DS . 'Autosuggest' . DS . 'Query.php');
            $this->_query = new IntegerNet_Solr_Autosuggest_Query();
        }

        return $this->_query;
    }

    /**
     * @return array
     * @todo add correct attributes
     */
    public function getFilterableInSearchAttributes()
    {
        return array();
    }
}