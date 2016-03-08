<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Product;
use IntegerNet\Solr\Implementor\Attribute;

class IntegerNet_Solr_Model_Bridge_Product implements Product
{
    /**
     * @var Mage_Catalog_Model_Product
     */
    protected $_product;

    /**
     * @param Mage_Catalog_Model_Product $_product
     */
    public function __construct(Mage_Catalog_Model_Product $_product)
    {
        $this->_product = $_product;
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getMagentoProduct()
    {
        return $this->_product;
    }


    public function getId()
    {
        return $this->_product->getId();
    }

    public function getStoreId()
    {
        return $this->_product->getStoreId();
    }

    public function isVisibleInCatalog()
    {
        return intval(in_array($this->_product->getVisibility(),
            Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds()));
    }

    public function isVisibleInSearch()
    {
        return intval(in_array($this->_product->getVisibility(),
            Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()));
    }

    public function getSolrBoost()
    {
        $this->_product->getData('solr_boost');
    }

    public function getPrice()
    {
        $price = $this->_product->getFinalPrice();
        if ($price == 0) {
            $price = $this->_product->getMinimalPrice();
        }
        $price = Mage::helper('tax')->getPrice($this->_product, $price, null, null, null, null, $this->_product->getStoreId());
        return $price;
    }

    public function getAttributeValue(Attribute $attribute)
    {
        return $this->_product->getData($attribute->getAttributeCode());
    }

    public function getSearchableAttributeValue(Attribute $attribute)
    {
        $magentoAttribute = Mage::getSingleton('integernet_solr/bridge_attributeRepository')->getMagentoAttribute($attribute);
        $value = trim(strip_tags($magentoAttribute->getFrontend()->getValue($this->_product)));
        $attributeCode = $attribute->getAttributeCode();
        if ($magentoAttribute->getData('backend_type') == 'int'
            && $magentoAttribute->getData('frontend_input') == 'select' 
            && $this->_product->getData($attributeCode) == 0) {
            return null;
        }
        if (! empty($value) && $attribute->getFacetType() == Attribute::FACET_TYPE_MULTISELECT) {
            $value = array_map('trim', explode(',', $value));
        }
        return $value;
    }


    public function getCategoryIds()
    {
        return $this->_product->getCategoryIds();
    }


    /**
     * @return \IntegerNet\Solr\Implementor\ProductIterator
     */
    public function getChildren()
    {
        $childProductIds = $this->_product->getTypeInstance(true)->getChildrenIds($this->_product->getId());

        if (sizeof($childProductIds) && is_array(current($childProductIds))) {
            $childProductIds = current($childProductIds);
        }

        if (!sizeof($childProductIds)) {
            Mage::throwException('Product ' . $this->_product->getSku() . ' doesn\'t have any child products.');
        }

        /** @var $childProductCollection Mage_Catalog_Model_Resource_Product_Collection */
        $childProductCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId($this->_product->getStoreId())
            ->addAttributeToFilter('entity_id', array('in' => $childProductIds))
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
            ->addAttributeToSelect(Mage::getSingleton('integernet_solr/bridge_attributeRepository')->getAttributeCodesToIndex());

        return new IntegerNet_Solr_Model_Bridge_ProductIterator($childProductCollection);

    }

    /**
     * @return int
     */
    public function getSolrId()
    {
        return $this->getId() . '_' . $this->getStoreId();
    }

    /**
     * @return bool
     */
    public function isIndexable()
    {
        Mage::dispatchEvent('integernet_solr_can_index_product', array('product' => $this->_product));

        if ($this->_product->getSolrExclude()) {
            return false;
        }
        if ($this->_product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            return false;
        }
        if (!in_array($this->_product->getVisibility(), Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds())) {
            return false;
        }
        if (!in_array($this->_product->getStore()->getWebsiteId(), $this->_product->getWebsiteIds())) {
            return false;
        }
        if (!$this->_product->getStockItem()->getIsInStock() && !Mage::helper('cataloginventory')->isShowOutOfStock()) {
            return false;
        }
        return true;

    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @deprecated only use interface methods!
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_product, $method), $args);
    }
}