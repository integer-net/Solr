<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;

class IntegerNet_Solr_Model_Bridge_AttributeRepository implements AttributeRepository
{
    /**
     * Holds attribute instances with their Magento attributes as attached data
     *
     * @var SplObjectStorage
     */
    protected $_attributeStorage;

    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection */
    protected $_searchableAttributes = null;

    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection */
    protected $_filterableInCatalogOrSearchAttributes = null;

    /** @var Mage_Eav_Model_Entity_Attribute[] */
    protected $_filterableInSearchAttributes = null;

    /** @var Mage_Eav_Model_Entity_Attribute[] */
    protected $_filterableInCatalogAttributes = null;

    /** @var Mage_Eav_Model_Entity_Attribute[] */
    protected $_sortableAttributes = null;

    public function __construct()
    {
        $this->_attributeStorage = new SplObjectStorage();
    }

    /**
     * Creates and registers bridge object for given Magento attribute
     *
     * @internal
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $magentoAttribute
     * @return IntegerNet_Solr_Model_Bridge_Attribute
     */
    public function _registerAttribute(Mage_Catalog_Model_Resource_Eav_Attribute $magentoAttribute)
    {
        $attribute = new IntegerNet_Solr_Model_Bridge_Attribute($magentoAttribute);
        $this->_attributeStorage->attach($attribute, $magentoAttribute);
        return $attribute;
    }

    /**
     * Returns Magento attribute for a given registered attribute instance
     * @param Attribute $attribute
     * @return mixed|null|object
     */
    public function getMagentoAttribute(Attribute $attribute)
    {
        if ($this->_attributeStorage->contains($attribute)) {
            return $this->_attributeStorage[$attribute];
        }
        return null;
    }

    /**
     * @return Attribute[]
     */
    public function getSearchableAttributes()
    {
        $this->_prepareSearchableAttributeCollection();

        return $this->_getAttributeArrayFromCollection($this->_searchableAttributes);
    }

    /**
     * @return Attribute[]
     */
    public function getSortableAttributes()
    {
        if (is_null($this->_sortableAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_sortableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('used_for_sort_by', 1)
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;
        }

        return $this->_getAttributeArrayFromCollection($this->_sortableAttributes);
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        if (Mage::helper('integernet_solr')->isCategoryPage()) {
            return $this->getFilterableInCatalogAttributes($useAlphabeticalSearch);
        } else {
            return $this->getFilterableInSearchAttributes($useAlphabeticalSearch);
        }
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true)
    {
        if (is_null($this->_filterableInSearchAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_filterableInSearchAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsFilterableInSearchFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;

            if ($useAlphabeticalSearch) {
                $this->_filterableInSearchAttributes
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_filterableInSearchAttributes
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }

        return $this->_getAttributeArrayFromCollection($this->_filterableInSearchAttributes);
    }


    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true)
    {
        if (is_null($this->_filterableInCatalogAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_filterableInCatalogAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsFilterableFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;

            if ($useAlphabeticalSearch) {
                $this->_filterableInCatalogAttributes
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_filterableInCatalogAttributes
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }

        return $this->_getAttributeArrayFromCollection($this->_filterableInCatalogAttributes);
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
    {
        $this->_prepareFilterableInCatalogOrSearchAttributeCollection($useAlphabeticalSearch);

        return $this->_getAttributeArrayFromCollection($this->_filterableInCatalogOrSearchAttributes);
    }

    /**
     * @return string[]
     */
    public function getAttributeCodesToIndex()
    {
        $this->_prepareFilterableInCatalogOrSearchAttributeCollection(true);
        $this->_prepareSearchableAttributeCollection();
        return array_merge(
            $this->_filterableInCatalogOrSearchAttributes->getColumnValues('attribute_code'),
            $this->_searchableAttributes->getColumnValues('attribute_code')
        );
    }

    /**
     * @param string $attributeCode
     * @return Attribute
     */
    public function getAttributeByCode($attributeCode)
    {
        $attribute = Mage::getModel('catalog/product')->getResource()->getAttribute($attributeCode);
        $attribute->setStoreId(Mage::app()->getStore()->getId());
        return $this->_registerAttribute($attribute);
    }

    /**
     * @param $useAlphabeticalSearch
     */
    protected function _prepareFilterableInCatalogOrSearchAttributeCollection($useAlphabeticalSearch)
    {
        if (is_null($this->_filterableInCatalogOrSearchAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_filterableInCatalogOrSearchAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter(
                    array(
                        'additional_table.is_filterable',
                        'additional_table.is_filterable_in_search'
                    ),
                    array(
                        array('gt' => 0),
                        array('gt' => 0),
                    )
                )
                ->addFieldToFilter('attribute_code', array('nin' => array('status')));

            if ($useAlphabeticalSearch) {
                $this->_filterableInCatalogOrSearchAttributes
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_filterableInCatalogOrSearchAttributes
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }
    }

    protected function _prepareSearchableAttributeCollection()
    {
        if (is_null($this->_searchableAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_searchableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsSearchableFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')));
        }
    }
    protected function _getAttributeArrayFromCollection(Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection)
    {
        $self = $this;
        return array_map(
            function($item) use ($self) {
                return $self->_registerAttribute($item);
            },
            $collection->getItems()
        );
    }
}