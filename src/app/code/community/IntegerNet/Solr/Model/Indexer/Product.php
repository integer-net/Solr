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
    /** @var IntegerNet_Solr_Block_Indexer_Item[] */
    protected $_itemBlocks = array();

    protected $_resourceName = 'integernet_solr/solr';

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
     * Generate single product data for Solr
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function _getProductData($product)
    {
        $productData = array(
            'id' => $this->_getSolrId($product), // primary identifier, must be unique
            'product_id' => $product->getId(),
            'category' => $product->getCategoryIds(),
            'store_id' => $product->getStoreId(),
            'content_type' => 'product',
        );

        $this->_addFacetsToProductData($product, $productData);

        $this->_addSearchDataToProductData($product, $productData);
        
        $this->_addResultHtmlToProductData($product, $productData);

        return $productData;
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
     * @param int $storeId
     * @return Mage_Catalog_Model_Resource_Product_Collection
     * @todo emulate store for getting correct reviews
     */
    protected function _getProductCollection($storeId)
    {
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId($storeId)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addAttributeToSelect(array('visibility', 'status', 'url_key'))
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getSearchableAttributes()->getColumnValues('attribute_code'))
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getFilterableInSearchAttributes()->getColumnValues('attribute_code'));

        Mage::dispatchEvent('catalog_block_product_list_collection', array(
            'collection' => $productCollection
        ));

        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        return $productCollection;
    }

    /**
     * Get unique identifier for Solr
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _getSolrId($product)
    {
        return $product->getId() . '_' . $product->getStoreId();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $productData
     */
    protected function _addFacetsToProductData($product, &$productData)
    {
        foreach (Mage::helper('integernet_solr')->getFilterableInSearchAttributes() as $attribute) {

            switch ($attribute->getFrontendInput()) {
                case 'select':
                    if ($rawValue = $product->getData($attribute->getAttributeCode())) {
                        $productData[$attribute->getAttributeCode() . '_facet'] = $rawValue;
                    }
                    break;
                case 'multiselect':
                    if ($rawValue = $product->getData($attribute->getAttributeCode())) {
                        $productData[$attribute->getAttributeCode() . '_facet'] = explode(',', $rawValue);
                    }
                    break;
            }

            $fieldName = Mage::helper('integernet_solr')->getFieldName($attribute);
            if (!isset($productData[$fieldName])) {
                $productData[$fieldName] = trim(strip_tags($attribute->getFrontend()->getValue($product)));
            }
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $productData
     */
    protected function _addSearchDataToProductData($product, &$productData)
    {
        foreach (Mage::helper('integernet_solr')->getSearchableAttributes() as $attribute) {

            if (get_class($attribute->getSource()) == 'Mage_Eav_Model_Entity_Attribute_Source_Boolean') {
                continue;
            }
            
            if ($attribute->getAttributeCode() == 'price') {
                $price = $product->getFinalPrice();
                if ($price == 0) {
                    $price = $product->getMinimalPrice();
                }
                $productData['price_f'] = $price;
                continue;
            }

            $attribute->setStoreId($product->getStoreId());

            if ($product->getData($attribute->getAttributeCode())
                && $value = trim(strip_tags($attribute->getFrontend()->getValue($product)))
            ) {
                $productData[Mage::helper('integernet_solr')->getFieldName($attribute)] = $value;
            }
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $productData
     */
    protected function _addResultHtmlToProductData($product, &$productData)
    {
        if (Mage::app()->getStore()->getId() != $product->getStoreId()) {
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $appEmulation->startEnvironmentEmulation($product->getStoreId());
        }

        /** @var IntegerNet_Solr_Block_Indexer_Item $block */
        $block = $this->_getResultItemBlock();

        $block->setProduct($product);
        
        $block->setTemplate('integernet/solr/result/list/item.phtml');
        $productData['result_html_list_t'] = $block->toHtml();
        
        $block->setTemplate('integernet/solr/result/grid/item.phtml');
        $productData['result_html_grid_t'] = $block->toHtml();
    }

    /**
     * @return IntegerNet_Solr_Block_Indexer_Item
     */
    protected function _getResultItemBlock()
    {
        if (!isset($this->_itemBlocks[Mage::app()->getStore()->getId()])) {
            /** @var IntegerNet_Solr_Block_Indexer_Item _itemBlock */
            $block = Mage::app()->getLayout()->createBlock('integernet_solr/indexer_item', 'solr_result_item');
            $this->_addPriceBlockTypes($block);
            $this->_itemBlocks[Mage::app()->getStore()->getId()] = $block;
        }

        return $this->_itemBlocks[Mage::app()->getStore()->getId()];
    }

    /**
     * @param IntegerNet_Solr_Block_Indexer_Item $block
     */
    protected function _addPriceBlockTypes($block)
    {
        $block->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'bundle/catalog/product/price.phtml');

        $priceBlockType = 'germansetup/catalog_product_price';
        if (Mage::app()->getLayout()->createBlock($priceBlockType)) {

            $block->addPriceBlockType('simple', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('virtual', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('grouped', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('downloadable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('configurable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('bundle', 'germansetup/bundle_catalog_product_price', 'bundle/catalog/product/price.phtml');
        }
        
        $priceBlockType = 'magesetup/catalog_product_price';
        if (Mage::app()->getLayout()->createBlock($priceBlockType)) {

            $block->addPriceBlockType('simple', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('virtual', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('grouped', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('downloadable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('configurable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('bundle', 'magesetup/bundle_catalog_product_price', 'bundle/catalog/product/price.phtml');
        }
    }

}