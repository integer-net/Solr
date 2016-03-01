<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Helper_Data extends Mage_Core_Helper_Abstract
{
    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_searchableAttributes = null;

    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_filterableInSearchAttributes = null;

    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_filterableInCatalogAttributes = null;

    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_filterableInCatalogOrSearchAttributes = null;

    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_sortableAttributes = null;

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getSearchableAttributes()
    {
        if (is_null($this->_searchableAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_searchableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsSearchableFilter()
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;
        }

        return $this->_searchableAttributes;
    }

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
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

        return $this->_sortableAttributes;
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        if ($this->isCategoryPage()) {
            return $this->getFilterableInCatalogAttributes($useAlphabeticalSearch);
        } else {
            return $this->getFilterableInSearchAttributes($useAlphabeticalSearch);
        }
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
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

        return $this->_filterableInSearchAttributes;
    }


    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
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

        return $this->_filterableInCatalogAttributes;
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
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
                ->addFieldToFilter('attribute_code', array('nin' => array('status')))
            ;

            if ($useAlphabeticalSearch) {
                $this->_filterableInCatalogOrSearchAttributes
                    ->setOrder('frontend_label', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            } else {
                $this->_filterableInCatalogOrSearchAttributes
                    ->setOrder('position', Mage_Eav_Model_Entity_Collection_Abstract::SORT_ORDER_ASC);
            }
        }

        return $this->_filterableInCatalogOrSearchAttributes;
    }


    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @param bool $forSorting
     * @return string
     */
    public function getFieldName($attribute, $forSorting = false)
    {
        if ($attribute->getUsedForSortBy()) {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f';

                case 'text':
                    return $attribute->getAttributeCode() . '_t';

                default:
                    return ($forSorting) ? $attribute->getAttributeCode() . '_s' : $attribute->getAttributeCode() . '_t';
            }
        } else {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f_mv';

                case 'text':
                    return $attribute->getAttributeCode() . '_t_mv';

                default:
                    return $attribute->getAttributeCode() . '_t_mv';
            }
        }
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active')) {
            return false;
        }

        if (!$this->isLicensed()) {
            return false;
        }

        if ($this->isCategoryPage() && !$this->isCategoryDisplayActive()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isCategoryPage()
    {
        return Mage::registry('current_category')
            && (
                Mage::app()->getRequest()->getModuleName() != 'catalogsearch'
                || Mage::app()->getRequest()->getControllerName() != 'result'
            );
    }

    /**
     * @return bool
     */
    public function isCategoryDisplayActive()
    {
        return Mage::getStoreConfigFlag('integernet_solr/category/is_active');
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isKeyValid($key)
    {
        if (!$key) {
            return true;
        }
        $key = trim(strtolower($key));
        $key = str_replace(array('-', '_', ' '), '', $key);

        if (strlen($key) != 10) {
            return false;
        }

        $hash = md5($key);

        return substr($hash, -3) == 'f11';
    }

    /**
     * @return bool
     */
    public function isLicensed()
    {
        if (!$this->isKeyValid(Mage::getStoreConfig('integernet_solr/general/license_key'))) {

            if ($installTimestamp = Mage::getStoreConfig('integernet_solr/general/install_date')) {

                $diff = time() - $installTimestamp;
                if (($diff < 0) || ($diff > 2419200)) {

                    Mage::log('The IntegerNet_Solr module is not correctly licensed. Please enter your license key at System -> Configuration -> Solr or contact us via http://www.integer-net.com/solr-magento/.', Zend_Log::WARN, 'exception.log');
                    return false;

                } else if ($diff > 1209600) {

                    Mage::log('The IntegerNet_Solr module is not correctly licensed. Please enter your license key at System -> Configuration -> Solr or contact us via http://www.integer-net.com/solr-magento/.', Zend_Log::WARN, 'exception.log');
                }
            }
        }

        return true;
    }
}