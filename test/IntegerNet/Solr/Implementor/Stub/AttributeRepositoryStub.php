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
use IntegerNet\Solr\Implementor\AttributeRepository;
use Mage_Catalog_Model_Entity_Attribute;
use Mage_Catalog_Model_Resource_Product_Attribute_Collection;
use BadMethodCallException;

class AttributeRepositoryStub implements AttributeRepository
{
    /**
     * @todo convert to IntegerNet\Solr\Implementor\Attribute array, maybe add getSearchableAttributeCodes()
     * @return \Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getSearchableAttributes()
    {
        return [AttributeStub::sortableString('attribute1'), AttributeStub::sortableString('attribute2')];
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        return [AttributeStub::sortableString('attribute1'), AttributeStub::sortableString('attribute2')];
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true)
    {
        throw new BadMethodCallException('not used in query builder');
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true)
    {
        throw new BadMethodCallException('not used in query builder');
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
    {
        throw new BadMethodCallException('not used in query builder');
    }

    /**
     * @return string[]
     */
    public function getAttributeCodesToIndex()
    {
        throw new BadMethodCallException('not used in query builder');
    }

    /**
     * @param string $attributeCode
     * @return Attribute
     */
    public function getAttributeByCode($attributeCode)
    {
        throw new BadMethodCallException('not used in query builder');
    }

}
