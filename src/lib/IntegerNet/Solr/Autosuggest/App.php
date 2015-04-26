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
 * This class is a low weight replacement for the "Mage_Core_Model_App" class in autosuggest calls
 *
 * Class IntegerNet_Solr_Autosuggest_App
 */
final class IntegerNet_Solr_Autosuggest_App
{
    protected $_store;
    protected $_storeId;

    public function __construct($_storeId)
    {
        $this->_storeId = $_storeId;
    }

    public function getStore()
    {
        if (is_null($this->_store)) {
            $this->_store = new IntegerNet_Solr_Autosuggest_Store($this->_storeId);
        }

        return $this->_store;
    }

    public function getLayout()
    {
        return false;
    }
}