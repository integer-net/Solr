<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Autosuggest_Source
{
    protected $_options = null;
    
    public function __construct($options)
    {
        $this->_options = $options;
    }
    
    public function getOptionText($optionId)
    {
        return $this->_options[$optionId];
    }
}