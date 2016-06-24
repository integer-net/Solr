<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Implementor\Stub;

use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\Source;

class AttributeStub implements Attribute
{
    /** @var  string */
    private $attributeCode;
    /** @var  string */
    private $storeLabel;
    /** @var  float */
    private $solrBoost;
    /** @var  Source */
    private $source;
    /** @var  string */
    private $backendType;
    /** @var  string */
    private $inputType;
    /** @var  bool */
    private $isSearchable;
    /** @var  bool */
    private $usedForSortBy;
    /** @var  string */
    private $facetType;
    /** @var  boolean */
    private $isSortable;

    public static function sortableString($name)
    {
        return new self($name, $name, 0, new SourceStub(), 'string', true, true, 'text', true, 'text');
    }
    public static function filterable($name, array $options)
    {
        return new self($name, $name, 0, new \IntegerNet\SolrSuggest\Plain\Entity\Source($options), 'int', true, false, 'int', false, 'text');
    }

    public function __construct($attributeCode, $storeLabel, $solrBoost, Source $source, $backendType, $isSearchable, $usedForSortBy, $facetType, $isSortable, $inputType)
    {
        $this->attributeCode = $attributeCode;
        $this->storeLabel = $storeLabel;
        $this->solrBoost = $solrBoost;
        $this->source = $source;
        $this->backendType = $backendType;
        $this->isSearchable = $isSearchable;
        $this->usedForSortBy = $usedForSortBy;
        $this->facetType = $facetType;
        $this->isSortable = $isSortable;
    }

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    /**
     * @param string $attributeCode
     */
    public function setAttributeCode($attributeCode)
    {
        $this->attributeCode = $attributeCode;
    }

    /**
     * @return string
     */
    public function getStoreLabel()
    {
        return $this->storeLabel;
    }

    /**
     * @param string $storeLabel
     */
    public function setStoreLabel($storeLabel)
    {
        $this->storeLabel = $storeLabel;
    }

    /**
     * @return float
     */
    public function getSolrBoost()
    {
        return $this->solrBoost;
    }

    /**
     * @param float $solrBoost
     */
    public function setSolrBoost($solrBoost)
    {
        $this->solrBoost = $solrBoost;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param Source $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getBackendType()
    {
        return $this->backendType;
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return $this->inputType;
    }

    /**
     * @param string $inputType
     */
    public function setInputType($inputType)
    {
        $this->inputType = $inputType;
    }

    /**
     * @param string $backendType
     */
    public function setBackendType($backendType)
    {
        $this->backendType = $backendType;
    }

    /**
     * @return boolean
     */
    public function getIsSearchable()
    {
        return $this->isSearchable;
    }

    /**
     * @param boolean $isSearchable
     */
    public function setIsSearchable($isSearchable)
    {
        $this->isSearchable = $isSearchable;
    }

    /**
     * @return boolean
     */
    public function getUsedForSortBy()
    {
        return $this->usedForSortBy;
    }

    /**
     * @param boolean $usedForSortBy
     */
    public function setUsedForSortBy($usedForSortBy)
    {
        $this->usedForSortBy = $usedForSortBy;
    }

    /**
     * @return string
     */
    public function getFacetType()
    {
        return $this->facetType;
    }

    /**
     * @param string $facetType
     */
    public function setFacetType($facetType)
    {
        $this->facetType = $facetType;
    }

}

class SourceStub implements Source
{
    /**
     * @param int $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        return '';
    }

    /**
     * Returns [optionId => optionText] map
     *
     * @return string[]
     */
    public function getOptionMap()
    {
        return array();
    }

}