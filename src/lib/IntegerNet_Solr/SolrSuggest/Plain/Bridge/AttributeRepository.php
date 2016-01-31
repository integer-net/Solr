<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Bridge;
use IntegerNet\Solr\Implementor\Attribute as AttributeInterface;
use IntegerNet\Solr\Implementor\AttributeRepository as AttributeRepositoryInterface;

class AttributeRepository implements AttributeRepositoryInterface
{
    /**
     * @return AttributeInterface[]
     */
    public function getSearchableAttributes()
    {
        $attributes = array();
        foreach((array)\IntegerNet_Solr_Autosuggest_Mage::getStoreConfig('searchable_attribute') as $attributeCode => $attributeConfig) {
            $attributes[$attributeCode] = new Attribute($attributeConfig);
        }

        return $attributes;
    }

    /**
     * @return AttributeInterface[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        return $this->getFilterableInSearchAttributes();
    }

    /**
     * @return AttributeInterface[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true)
    {
        $attributes = array();
        foreach((array)\IntegerNet_Solr_Autosuggest_Mage::getStoreConfig('attribute') as $attributeCode => $attributeConfig) {
            $attributes[$attributeCode] = new Attribute($attributeConfig);
        }

        return $attributes;
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return AttributeInterface[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true)
    {
        // not used in autosuggest
        return array();
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
    {
        // not used in autosuggest
        return array();
    }

    /**
     * @return string[]
     */
    public function getAttributeCodesToIndex()
    {
        // not used in autosuggest
        return array();
    }
    /**
     * @param string $attributeCode
     * @return AttributeInterface
     */
    public function getAttributeByCode($attributeCode)
    {
        return new Attribute(\IntegerNet_Solr_Autosuggest_Mage::getStoreConfig('attribute/' . $attributeCode));;
    }

}