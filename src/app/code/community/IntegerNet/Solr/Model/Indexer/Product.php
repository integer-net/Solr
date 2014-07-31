<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Indexer_Product extends Mage_Core_Model_Abstract
{
    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_attributes = array();

    protected $_resourceName = 'integernet_solr/indexer';

    /**
     * @param array|null $productIds
     * @param boolean $emptyIndex
     */
    public function reindex($productIds = null, $emptyIndex = false)
    {
        foreach(Mage::app()->getStores() as $store) {

            /** @var Mage_Core_Model_Store $store */
            $storeId = $store->getId();

            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
                continue;
            }

            /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
            $productCollection = Mage::getResourceModel('catalog/product_collection')
                ->setStoreId($storeId)
                ->addUrlRewrite()
                ->addAttributeToSelect(array('visibility', 'status'))
                ->addAttributeToSelect($this->_getSearchableAttributeCodes());

            if (is_array($productIds)) {
                $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
            }

            $combinedProductData = array();
            $productSkusForDeletion = array();

            foreach($productCollection as $product) {
                /** @var Mage_Catalog_Model_Product $product */
                $product->setStoreId($storeId);
                $productData = $this->_getProductData($product);
                if (is_array($productData)) {
                    $combinedProductData[] = $productData;
                } else {
                    $productSkusForDeletion[] = $product->getSku();
                }
            }

            if ($emptyIndex) {
                $this->getResource()->deleteAllDocuments($storeId);
            } else {
                if (sizeof($productSkusForDeletion)) {
                    $this->getResource()->deleteByMultipleIds($storeId, $productSkusForDeletion);
                }
            }

            if (sizeof($combinedProductData)) {
                $this->getResource()->addDocuments($storeId, $combinedProductData);
            }
        }
    }

    /**
     * @param string[] $productIds
     */
    public function deleteIndex($productIds)
    {
        foreach(Mage::app()->getStores() as $store) {

            /** @var Mage_Core_Model_Store $store */
            $storeId = $store->getId();

            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
                continue;
            }

            $ids = array();

            foreach($productIds as $productId) {
                $ids[] = $productId . '_' . $storeId;
            }

            $this->getResource()->deleteByMultipleIds($storeId, $ids);
        }
    }


    /**
     * @param Mage_Catalog_Model_Product $product
     * @return array|null
     */
    protected function _getProductData($product)
    {
        if ($this->_canIndexProduct($product)) {
            return array(
                'id' => $product->getId() . '_' . $product->getStoreId(), // primary identifier, must be unique
                'product_id' => $product->getId(),
                'name' => $product->getName(),
                'store_id' => $product->getStoreId(),
                'content_type' => 'product',
            );
        }

        return null;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    protected function _canIndexProduct($product)
    {
        if (!$product->isVisibleInCatalog()) {
            return false;
        }

        return true;
    }

    /**
     * @return string[]
     */
    protected function _getSearchableAttributeCodes()
    {
        $attributeCodes = array(
            'name',
            'description',
            'short_description',
        );

        return $attributeCodes;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param string $attributeCode
     * @return string
     */
    protected function _getRawAttributeValue(Mage_Catalog_Model_Product $product, $attributeCode)
    {
        /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $attribute = $this->_getAttribute($attributeCode);
        if (!$attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
            return null;
        }

        switch($attribute->getFrontendInput()) {
            case 'textarea':
                return str_replace(array("\r\n", "\n", "\r"), '<br />', trim($product->getData($attributeCode)));
            case 'select':
                return $product->getAttributeText($attributeCode);
            case 'price':
                return $this->_formatPrice($product->getData($attributeCode), 2, '.', '');
            case 'date':
                $date = new Zend_Date($product->getData($attributeCode), Zend_Date::ISO_8601);
                $date->setTimezone(Mage::getStoreConfig('general/locale/timezone'));
                return $date->get(Zend_Date::ISO_8601);
            default:
                if (is_array($product->getData($attributeCode))) {
                    return implode(', ', $product->getData($attributeCode));
                }
                return trim($product->getData($attributeCode));
        }
    }

    /**
     * @param string $attributeCode
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function _getAttribute($attributeCode)
    {
        if (!isset($this->_attributes[$attributeCode])) {
            $this->_attributes[$attributeCode] = Mage::getSingleton('eav/config')
                ->getAttribute('catalog_product', $attributeCode);
        }

        return $this->_attributes[$attributeCode];
    }

    /**
     * @param float $price
     * @return string
     */
    protected function _formatPrice($price)
    {
        return number_format($price, 2, '.', '');
    }
}