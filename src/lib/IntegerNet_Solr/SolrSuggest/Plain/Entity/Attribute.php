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

final class Attribute implements SerializableAttribute
{
    protected $_attributeConfig = null;
    /**
     * @var
     */
    private $attributeCode;
    /**
     * @var
     */
    private $label;
    /**
     * @var
     */
    private $solrBoost;
    /**
     * @var SerializableSource
     */
    private $source;
    /**
     * @var
     */
    private $usedForSortBy;

    /**
     * @param string $attributeCode
     * @param string $label
     * @param float $solrBoost
     * @param SerializableSource $source
     * @param bool $usedForSortBy
     * @param array $customData
     */
    public function __construct($attributeCode, $label, $solrBoost, SerializableSource $source, $usedForSortBy, array $customData)
    {
        $this->_attributeConfig = $customData;
        $this->attributeCode = $attributeCode;
        $this->label = $label;
        $this->solrBoost = $solrBoost;
        $this->source = $source;
        $this->usedForSortBy = $usedForSortBy;
    }

    /**
     * @deprecated explicit constructor is preferred
     * @param $array
     * @return Attribute
     */
    public static function fromArray($array)
    {
        $options = isset($array['options']) ? $array['options'] : [];
        $source = new Source($options);
        $usedForSortBy = isset($array['used_for_sortby']) ? $array['used_for_sortby'] : false;
        return new self($array['attribute_code'], $array['label'], @$array['solr_boost'], $source, $usedForSortBy, $array);
    }

    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    public function getStoreLabel()
    {
        return $this->label;
    }

    public function getSolrBoost()
    {
        return $this->solrBoost;
    }

    public function getSource()
    {
        return $this->source;
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
        return $this->usedForSortBy;
    }

    public function getFacetType()
    {
        throw new \BadMethodCallException('only used for indexer, not needed in plain mode');
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getCustomData($key)
    {
        return $this->_attributeConfig[$key];
    }

}