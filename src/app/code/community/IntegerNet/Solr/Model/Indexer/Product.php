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
    protected $_searchableAttributes = null;

    /** @var Mage_Catalog_Model_Entity_Attribute[] */
    protected $_filterableInSearchAttributes = null;

    protected $_resourceName = 'integernet_solr/indexer';

    /**
     * @param array|null $productIds Restrict to given Products if this is set
     * @param boolean $emptyIndex Whether to truncate the index before refilling it 
     */
    public function reindex($productIds = null, $emptyIndex = false)
    {
        foreach(Mage::app()->getStores() as $store) {

            /** @var Mage_Core_Model_Store $store */
            $storeId = $store->getId();

            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
                continue;
            }
            
            $productCollection = $this->_getProductCollection($storeId);

            if (is_array($productIds)) {
                $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
            }

            $combinedProductData = array();
            $idsForDeletion = array();

            foreach($productCollection as $product) {
                /** @var Mage_Catalog_Model_Product $product */
                
                $product->setStoreId($storeId);
                if ($this->_canIndexProduct($product)) {
                    $combinedProductData[] = $this->_getProductData($product);
                } else {
                    $idsForDeletion[] = $this->_getSolrId($product);
                }
            }

            if ($emptyIndex) {
                $this->getResource()->deleteAllDocuments($storeId);
            } else {
                if (sizeof($idsForDeletion)) {
                    $this->getResource()->deleteByMultipleIds($storeId, $idsForDeletion);
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
     * @return array
     */
    protected function _getProductData($product)
    {
        return array(
            'id' => $this->_getSolrId($product), // primary identifier, must be unique
            'product_id' => $product->getId(),
            'name' => $product->getName(),
            'store_id' => $product->getStoreId(),
            'content_type' => 'product',
        );
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    protected function _canIndexProduct($product)
    {
        if ($product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            return false;
        }
        if (!in_array($product->getVisibility(), Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds())) {
            return false;
        } 
        if (!in_array($product->getStore()->getWebsiteId(), $product->getWebsiteIds())) {
            return false;
        }
        if (!$product->getStockItem()->getIsInStock() && !Mage::helper('cataloginventory')->isShowOutOfStock()) {
            return false;
        }
        return true;
    }

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    protected function _getSearchableAttributes()
    {
        if (is_null($this->_searchableAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_searchableAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsSearchableFilter();
        }

        return $this->_searchableAttributes;
    }

    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    protected function _getFilterableInSearchAttributes()
    {
        if (is_null($this->_filterableInSearchAttributes)) {

            /** @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
            $this->_filterableInSearchAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addIsFilterableInSearchFilter();
        }

        return $this->_filterableInSearchAttributes;
    }

    /**
     * @param $storeId
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _getProductCollection($storeId)
    {
        /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId($storeId)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addAttributeToSelect(array('visibility', 'status'))
            ->addAttributeToSelect($this->_getSearchableAttributes()->getColumnValues('attribute_code'))
            ->addAttributeToSelect($this->_getFilterableInSearchAttributes()->getColumnValues('attribute_code'));
        return $productCollection;
    }

    /**
     * @param $product
     * @return string
     */
    protected function _getSolrId($product)
    {
        return $product->getId() . '_' . $product->getStoreId();
    }
}