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
use IntegerNet\Solr\Exception;
use IntegerNet\Solr\Implementor\Attribute as AttributeInterface;
use IntegerNet\Solr\Implementor\AttributeRepository as AttributeRepositoryInterface;
use IntegerNet\SolrSuggest\Plain\Entity\SerializableAttribute;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;

class AttributeRepository implements AttributeRepositoryInterface
{
    const DEFAULT_STORE_ID = 0;
    /**
     * @var CacheReader
     */
    private $cacheReader;
    /**
     * @var SerializableAttribute[][]
     */
    private $filterableAttributes = array();

    /**
     * @param CacheReader $cacheReader
     */
    public function __construct(CacheReader $cacheReader)
    {
        $this->cacheReader = $cacheReader;
    }

    /**
     * @param int $storeId
     * @return SerializableAttribute[]
     */
    private function findFilterableInSearchAttributes($storeId)
    {
        if (! isset($this->filterableAttributes[$storeId])) {
            $this->filterableAttributes[$storeId] = array();
            foreach ($this->cacheReader->getFilterableAttributes($storeId) as $attribute) {
                $this->filterableAttributes[$storeId][$attribute->getAttributeCode()] = $attribute;
            }
        }
        return $this->filterableAttributes[$storeId];
    }

    /**
     * @param $storeId
     * @return SerializableAttribute[]
     */
    private function findSearchableAttributes($storeId)
    {
        return $this->cacheReader->getSearchableAttributes($storeId);
    }

    /**
     * @param int $storeId
     * @return AttributeInterface[]
     */
    public function getSearchableAttributes($storeId)
    {
        return $this->findSearchableAttributes($storeId);
    }

    /**
     * @param int $storeId
     * @return AttributeInterface[]
     */
    public function getFilterableAttributes($storeId, $useAlphabeticalSearch = true)
    {
        return $this->getFilterableInSearchAttributes($storeId);
    }

    /**
     * @param int $storeId
     * @return AttributeInterface[]
     */
    public function getFilterableInSearchAttributes($storeId, $useAlphabeticalSearch = true)
    {
        return $this->findFilterableInSearchAttributes($storeId);
    }

    /**
     * @param int $storeId
     * @param bool $useAlphabeticalSearch
     * @return AttributeInterface[]
     */
    public function getFilterableInCatalogAttributes($storeId, $useAlphabeticalSearch = true)
    {
        // not used in autosuggest
        return array();
    }

    /**
     * @param int $storeId
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($storeId, $useAlphabeticalSearch = true)
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
     * @param int $storeId
     * @param string $attributeCode
     * @return AttributeInterface
     * @throws Exception
     */
    public function getAttributeByCode($storeId, $attributeCode)
    {
        $attributes = $this->findFilterableInSearchAttributes($storeId);
        if (! isset($attributes[$attributeCode])) {
            throw new Exception('Attribute not found: ' . $attributeCode);
        }
        return $attributes[$attributeCode];
    }

}