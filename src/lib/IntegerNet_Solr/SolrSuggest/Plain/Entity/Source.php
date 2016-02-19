<?php
namespace IntegerNet\SolrSuggest\Plain\Entity;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
final class Source implements SerializableSource
{
    protected $_options = null;

    public function __construct($options)
    {
        $this->_options = $options;
    }

    public function getOptionText($optionId)
    {
        return isset($this->_options[$optionId]) ? $this->_options[$optionId] : '';
    }
}