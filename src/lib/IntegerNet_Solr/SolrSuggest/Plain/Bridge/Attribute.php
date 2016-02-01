<?php
namespace IntegerNet\SolrSuggest\Plain\Bridge;

use IntegerNet\SolrSuggest\Implementor\SerializableAttribute;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
final class Attribute implements SerializableAttribute
{
    protected $_attributeConfig = null;
    protected $_source = null;

    public function __construct($attributeConfig)
    {
        $this->_attributeConfig = $attributeConfig;
    }

    public function getAttributeCode()
    {
        return $this->_attributeConfig['attribute_code'];
    }

    public function getStoreLabel()
    {
        return $this->_attributeConfig['label'];
    }

    public function getSolrBoost()
    {
        return $this->_attributeConfig['solr_boost'];
    }

    public function getSource()
    {
        if (is_null($this->_source)) {
            $this->_source = new Source($this->_attributeConfig['options']);
        }
        return $this->_source;
    }

    public function getIsSearchable()
    {
        return true;
    }

    public function getBackendType()
    {
        return 'varchar';
    }

    public function getUsedForSortBy()
    {
        return $this->_attributeConfig['used_for_sortby'];
    }

    public function getFacetType()
    {
        return $this->_attributeConfig['frontend_input'];
    }

}