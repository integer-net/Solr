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
use IntegerNet\SolrSuggest\Implementor\SerializableAttributeRepository;

class IntegerNet_Solr_Model_Bridge_AttributeRepository implements AttributeRepository
{
    const DEFAULT_STORE_ID = 1;
    /**
     * Holds attribute instances with their Magento attributes as attached data
     *
     * @var SplObjectStorage
     */
    protected $_attributeStorage;

    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection[] */
    protected $_searchableAttributes = array();

    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection[] */
    protected $_filterableInCatalogOrSearchAttributes = array();

    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection[] */
    protected $_filterableInSearchAttributes = array();

    /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection[] */
    protected $_filterableInCatalogAttributes = array();

    /** @var Mage_Eav_Model_Entity_Attribute[] */
    protected $_varcharProductAttributes = null;

    /** @var Mage_Eav_Model_Entity_Attribute[] */
    protected $_varcharCategoryAttributes = null;

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
     * @return null|Mage_Catalog_Model_Resource_Eav_Attribute
     */
    public function getMagentoAttribute(Attribute $attribute)
    {
        if ($this->_attributeStorage->contains($attribute)) {
            return $this->_attributeStorage[$attribute];
        }
        return null;
    }

    /**
     * @param int $storeId
     * @return Attribute[]
     */
    public function getSearchableAttributes($storeId)
    {
        $this->_prepareSearchableAttributeCollection($storeId);

        return $this->_getAttributeArrayFromCollection($this->_searchableAttributes[$storeId], $storeId);
    }

    /**
     * @return Attribute[]
     */
    public function getSortableAttributes()
    {
        if (is_null($this->_sortableAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_sortableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('used_for_sort_by', self::DEFAULT_STORE_ID)
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;
        }

        return $this->_getAttributeArrayFromCollection($this->_sortableAttributes, self::DEFAULT_STORE_ID);
    }

    /**
     * @param int $storeId
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableAttributes($storeId, $useAlphabeticalSearch = true)
    {
        if (Mage::helper('integernet_solr')->isCategoryPage()) {
            return $this->getFilterableInCatalogAttributes($storeId, $useAlphabeticalSearch);
        } else {
            return $this->getFilterableInSearchAttributes($storeId, $useAlphabeticalSearch);
        }
    }

    /**
     * @param int $storeId
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInSearchAttributes($storeId, $useAlphabeticalSearch = true)
    {
        if (! isset($this->_filterableInSearchAttributes[$storeId])) {

            $this->_filterableInSearchAttributes[$storeId] = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addStoreLabel($storeId)
                ->addIsFilterableInSearchFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;

            if ($useAlphabeticalSearch) {
                $this->_filterableInSearchAttributes[$storeId]
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_filterableInSearchAttributes[$storeId]
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }

        return $this->_getAttributeArrayFromCollection($this->_filterableInSearchAttributes[$storeId], $storeId);
    }


    /**
     * @param int $storeId
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogAttributes($storeId, $useAlphabeticalSearch = true)
    {
        if (! isset($this->_filterableInCatalogAttributes[$storeId])) {

            $this->_filterableInCatalogAttributes[$storeId] = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addStoreLabel($storeId)
                ->addIsFilterableFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;

            if ($useAlphabeticalSearch) {
                $this->_filterableInCatalogAttributes[$storeId]
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_filterableInCatalogAttributes[$storeId]
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }

        return $this->_getAttributeArrayFromCollection($this->_filterableInCatalogAttributes[$storeId], $storeId);
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getVarcharProductAttributes($useAlphabeticalSearch = true)
    {
        if (is_null($this->_varcharProductAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_varcharProductAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('backend_type', array('in' => array('static', 'varchar')))
                ->addFieldToFilter('frontend_input', 'text')
                ->addFieldToFilter('attribute_code', array('nin' => array(
                    'url_path',
                    'image_label',
                    'small_image_label',
                    'thumbnail_label',
                    'category_ids',
                    'required_options',
                    'has_options',
                    'created_at',
                    'updated_at',
                )))
            ;

            if ($useAlphabeticalSearch) {
                $this->_varcharProductAttributes
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_varcharProductAttributes
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }

        return $this->_getAttributeArrayFromCollection($this->_varcharProductAttributes, self::DEFAULT_STORE_ID);
    }

    /**
     * @param int $storeId
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($storeId, $useAlphabeticalSearch = true)
    {
        $this->_prepareFilterableInCatalogOrSearchAttributeCollection($useAlphabeticalSearch, $storeId);

        return $this->_getAttributeArrayFromCollection($this->_filterableInCatalogOrSearchAttributes[$storeId], $storeId);
    }

    /**
     * @return string[]
     */
    public function getAttributeCodesToIndex()
    {
        $this->_prepareFilterableInCatalogOrSearchAttributeCollection(true, self::DEFAULT_STORE_ID);
        $this->_prepareSearchableAttributeCollection(self::DEFAULT_STORE_ID);
        return array_merge(
            $this->_filterableInCatalogOrSearchAttributes[self::DEFAULT_STORE_ID]->getColumnValues('attribute_code'),
            $this->_searchableAttributes[self::DEFAULT_STORE_ID]->getColumnValues('attribute_code')
        );
    }

    /**
     * @param int $storeId
     * @param string $attributeCode
     * @return Attribute
     * @deprecated not part of AttributeRepository interface anymore, should not be needed
     */
    public function getAttributeByCode($storeId, $attributeCode)
    {
        $attribute = Mage::getModel('catalog/product')->getResource()->getAttribute($attributeCode);
        $attribute->setStoreId($storeId);
        return $this->_registerAttribute($attribute);
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @param int $storeId
     */
    protected function _prepareFilterableInCatalogOrSearchAttributeCollection($useAlphabeticalSearch, $storeId)
    {
        if (! isset($this->_filterableInCatalogOrSearchAttributes[$storeId])) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_filterableInCatalogOrSearchAttributes[$storeId] = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addStoreLabel($storeId)
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
                $this->_filterableInCatalogOrSearchAttributes[$storeId]
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_filterableInCatalogOrSearchAttributes[$storeId]
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }
    }

    protected function _prepareSearchableAttributeCollection($storeId)
    {
        if (! isset($this->_searchableAttributes[$storeId])) {

            $this->_searchableAttributes[$storeId] = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addStoreLabel($storeId)
                ->addIsSearchableFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
                ->addFieldToFilter('source_model', array(
                    array('neq' => 'eav/entity_attribute_source_boolean'),
                    array('null' => true)
                ));
        }
    }
    protected function _getAttributeArrayFromCollection(Mage_Eav_Model_Resource_Entity_Attribute_Collection $collection, $storeId)
    {
        $self = $this;
        return array_map(
            function($item) use ($self, $storeId) {
                $item->setStoreId($storeId);
                return $self->_registerAttribute($item);
            },
            $collection->getItems()
        );
    }

}