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
    
    protected $_pathCategoryIds = array();
    
    protected $_excludedCategoryIds = null;

    /**
     * @param array|null $productIds Restrict to given Products if this is set
     * @param boolean $emptyIndex Whether to truncate the index before refilling it 
     */
    public function reindex($productIds = null, $emptyIndex = false)
    {
        $pageSize = intval(Mage::getStoreConfig('integernet_solr/indexing/pagesize'));
        if ($pageSize <= 0) {
            $pageSize = 100;
        }
        
        foreach(Mage::app()->getStores() as $store) {
            /** @var Mage_Core_Model_Store $store */

            $storeId = $store->getId();

            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
                continue;
            }

            if ($emptyIndex) {
                $this->getResource()->deleteAllDocuments($storeId);
            }

            $pageNumber = 1;
            do {
                $productCollection = $this->_getProductCollection($storeId);
    
                if (is_array($productIds)) {
                    $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
                }
    
                $productCollection->setPageSize($pageSize);
                $productCollection->setCurPage($pageNumber++);
    
                $this->_indexProductCollection($emptyIndex, $productCollection);

            } while ($pageNumber <= $productCollection->getLastPageNumber());
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
        $productData = new Varien_Object(array(
            'id' => $this->_getSolrId($product), // primary identifier, must be unique
            'product_id' => $product->getId(),
            'category' => $this->_getCategoryIds($product), // @todo get category ids from parent anchor categories as well
            'store_id' => $product->getStoreId(),
            'content_type' => 'product',
        ));

        $this->_addBoostToProductData($product, $productData);

        $this->_addFacetsToProductData($product, $productData);

        $this->_addSearchDataToProductData($product, $productData);
        
        $this->_addResultHtmlToProductData($product, $productData);
        
        Mage::dispatchEvent('integernet_solr_get_product_data', array('product' => $product, 'product_data' => $productData));

        return $productData->getData();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    protected function _canIndexProduct($product)
    {
        Mage::dispatchEvent('integernet_solr_can_index_product', array('product' => $product));
        
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
     */
    protected function _getProductCollection($storeId)
    {
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        Mage::app()->getStore()->setConfig('catalog/frontend/flat_catalog_product', 0);

        /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId($storeId)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addAttributeToSelect(array('visibility', 'status', 'url_key', 'solr_boost'))
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getSearchableAttributes()->getColumnValues('attribute_code'))
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getFilterableInSearchAttributes()->getColumnValues('attribute_code'));

/*        Mage::dispatchEvent('catalog_block_product_list_collection', array(
            'collection' => $productCollection
        ));*/

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
     * @param Varien_Object $productData
     */
    protected function _addFacetsToProductData($product, $productData)
    {
        foreach (Mage::helper('integernet_solr')->getFilterableInSearchAttributes() as $attribute) {

            switch ($attribute->getFrontendInput()) {
                case 'select':
                    if ($rawValue = $product->getData($attribute->getAttributeCode())) {
                        $productData->setData($attribute->getAttributeCode() . '_facet', $rawValue);
                    }
                    break;
                case 'multiselect':
                    if ($rawValue = $product->getData($attribute->getAttributeCode())) {
                        $productData->setData($attribute->getAttributeCode() . '_facet', explode(',', $rawValue));
                    }
                    break;
            }

            $fieldName = Mage::helper('integernet_solr')->getFieldName($attribute);
            if (!$productData->hasData($fieldName)) {
                $productData->setData($fieldName, trim(strip_tags($attribute->getFrontend()->getValue($product))));
            }
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $productData
     */
    protected function _addSearchDataToProductData($product, $productData)
    {
        $childProducts = $this->_getChildProductsCollection($product);

        foreach (Mage::helper('integernet_solr')->getSearchableAttributes() as $attribute) {

            if (get_class($attribute->getSource()) == 'Mage_Eav_Model_Entity_Attribute_Source_Boolean') {
                continue;
            }

            $fieldName = Mage::helper('integernet_solr')->getFieldName($attribute);

            $solrBoost = floatval($attribute->getSolrBoost());
            if ($solrBoost != 1) {
                $productData->setData($fieldName . '_boost', $solrBoost);
            }
            
            if ($attribute->getAttributeCode() == 'price') {
                $price = $product->getFinalPrice();
                if ($price == 0) {
                    $price = $product->getMinimalPrice();
                }
                $productData->setData('price_f', floatval($price));
                continue;
            }

            $attribute->setStoreId($product->getStoreId());

            if ($product->getData($attribute->getAttributeCode())
                && $value = trim(strip_tags($attribute->getFrontend()->getValue($product)))
            ) {
                $productData->setData($fieldName, $value);
            }

            if ($attribute->getBackendType() != 'decimal') {
                foreach($childProducts as $childProduct) {

                    if ($childProduct->getData($attribute->getAttributeCode())
                        && $childValue = trim(strip_tags($attribute->getFrontend()->getValue($childProduct)))
                    ) {
                        if (!$productData->hasData($fieldName)) {
                            $productData->setData($fieldName, $childValue);
                        } else {
                            if (!$attribute->getUsedForSortBy()) {
                                $fieldValue = $productData->getData($fieldName);
                                if (!is_array($fieldValue) && $childValue != $fieldValue) {
                                    $productData->setData($fieldName, array($fieldValue, $childValue));
                                } else {
                                    if (is_array($fieldValue) && !in_array($childValue, $fieldValue)) {
                                        $fieldValue[] = $childValue;
                                        $productData->setData($fieldName, $fieldValue);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $productData
     */
    protected function _addResultHtmlToProductData($product, $productData)
    {
        if (Mage::app()->getStore()->getId() != $product->getStoreId()) {
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $appEmulation->startEnvironmentEmulation($product->getStoreId());
        }

        /** @var IntegerNet_Solr_Block_Indexer_Item $block */
        $block = $this->_getResultItemBlock();

        $block->setProduct($product);
        
        $block->setTemplate('integernet/solr/result/autosuggest/item.phtml');
        $productData->setData('result_html_autosuggest_nonindex', $block->toHtml());

        if (Mage::getStoreConfig('integernet_solr/results/use_html_from_solr')) {
            $block->setTemplate('integernet/solr/result/list/item.phtml');
            $productData->setData('result_html_list_nonindex', $block->toHtml());
            
            $block->setTemplate('integernet/solr/result/grid/item.phtml');
            $productData->setData('result_html_grid_nonindex', $block->toHtml());
        }
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
            // support for rwd theme
            $block->setChild('name.after', Mage::app()->getLayout()->createBlock('core/text_list'));
            $block->setChild('after', Mage::app()->getLayout()->createBlock('core/text_list'));
            $this->_itemBlocks[Mage::app()->getStore()->getId()] = $block;
        }

        return $this->_itemBlocks[Mage::app()->getStore()->getId()];
    }

    /**
     * Add custom price blocks for correct price display
     * 
     * @param IntegerNet_Solr_Block_Indexer_Item $block
     */
    protected function _addPriceBlockTypes($block)
    {
        $block->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'bundle/catalog/product/price.phtml');

        $priceBlockType = 'germansetup/catalog_product_price';
        if (class_exists(Mage::getConfig()->getBlockClassName($priceBlockType)) && Mage::app()->getLayout()->createBlock($priceBlockType)) {

            $block->addPriceBlockType('simple', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('virtual', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('grouped', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('downloadable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('configurable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('bundle', 'germansetup/bundle_catalog_product_price', 'bundle/catalog/product/price.phtml');
        }
        
        $priceBlockType = 'magesetup/catalog_product_price';
        if (class_exists(Mage::getConfig()->getBlockClassName($priceBlockType)) && Mage::app()->getLayout()->createBlock($priceBlockType)) {

            $block->addPriceBlockType('simple', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('virtual', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('grouped', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('downloadable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('configurable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('bundle', 'magesetup/bundle_catalog_product_price', 'bundle/catalog/product/price.phtml');
        }
    }

    /**
     * Get category ids of assigned categories and all parents
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return int[]
     */
    protected function _getCategoryIds($product)
    {
        $categoryIds = $product->getCategoryIds();
        
        if (!sizeof($categoryIds)) {
            return array();
        }
 
        $lookupCategoryIds = array_diff($categoryIds, array_keys($this->_pathCategoryIds));
        $this->_lookupCategoryIdPaths($lookupCategoryIds, $product->getStoreId());

        $foundCategoryIds = array();
        foreach($categoryIds as $categoryId) {
            $categoryPathIds = $this->_pathCategoryIds[$categoryId];
            $foundCategoryIds = array_merge($foundCategoryIds, $categoryPathIds);
        }

        $foundCategoryIds = array_unique($foundCategoryIds);
        
        $foundCategoryIds = array_diff($foundCategoryIds, $this->_getExcludedCategoryIds($product->getStoreId()));

        return $foundCategoryIds;
    }

    /**
     * Lookup and store all parent category ids and its own id of given category ids
     * 
     * @param int[] $categoryIds
     * @param int $storeId
     */
    protected function _lookupCategoryIdPaths($categoryIds, $storeId)
    {
        if (!sizeof($categoryIds)) {
            return;
        }

        /** @var $categories Mage_Catalog_Model_Resource_Category_Collection */
        $categories = Mage::getResourceModel('catalog/category_collection')
            ->addAttributeToFilter('entity_id', array('in' => $categoryIds))
            ->addAttributeToSelect(array('is_active', 'include_in_menu'));

        foreach ($categories as $category) {
            /** @var Mage_Catalog_Model_Category $categoryPathIds */
            if (!$category->getIsActive() || !$category->getIncludeInMenu()) {
                $this->_pathCategoryIds[$category->getId()] = array();
                continue;
            }
            
            $categoryPathIds = explode('/', $category->getPath());
            if (!in_array(Mage::app()->getStore($storeId)->getGroup()->getRootCategoryId(), $categoryPathIds)) {
                $this->_pathCategoryIds[$category->getId()] = array();
                continue;
            }

            array_shift($categoryPathIds);
            array_shift($categoryPathIds);
            $this->_pathCategoryIds[$category->getId()] = $categoryPathIds;
        }
    }

    /**
     * @param int $storeId
     * @return array
     */
    protected function _getExcludedCategoryIds($storeId) 
    {
        if (is_null($this->_excludedCategoryIds)) {

            /** @var $excludedCategories Mage_Catalog_Model_Resource_Category_Collection */
            $excludedCategories = Mage::getResourceModel('catalog/category_collection')
                ->addFieldToFilter('solr_exclude', 1);

            $this->_excludedCategoryIds = $excludedCategories->getAllIds();

            /** @var $categoriesWithChildrenExcluded Mage_Catalog_Model_Resource_Category_Collection */
            $categoriesWithChildrenExcluded = Mage::getResourceModel('catalog/category_collection')
                ->setStoreId($storeId)
                ->addFieldToFilter('solr_exclude_children', 1);

            $excludePaths = $categoriesWithChildrenExcluded->getColumnValues('path');

            /** @var $excludedChildrenCategories Mage_Catalog_Model_Resource_Category_Collection */
            $excludedChildrenCategories = Mage::getResourceModel('catalog/category_collection')
                ->setStoreId($storeId);
            
            $excludePathConditions = array();
            foreach($excludePaths as $excludePath) {
                $excludePathConditions[] = array('like' => $excludePath . '/%');
            }
            if (sizeof($excludePathConditions)) {
                $excludedChildrenCategories->addAttributeToFilter('path', $excludePathConditions);
                $this->_excludedCategoryIds = array_merge($this->_excludedCategoryIds, $excludedChildrenCategories->getAllIds());
            }
        }
        
        return $this->_excludedCategoryIds;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $productData
     */
    protected function _addBoostToProductData($product, &$productData)
    {
        if ($boost = $product->getSolrBoost()) {
            if ($boost > 0) {
                $productData->setData('_boost', $boost);
            }
        }
    }

    /**
     * Retrieve model resource
     *
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    public function getResource()
    {
        return $this->_getResource();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _getChildProductsCollection($product)
    {
        $childProductIds = $product->getTypeInstance(true)->getChildrenIds($product->getId());

        /** @var $childProductCollection Mage_Catalog_Model_Resource_Product_Collection */
        $childProductCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId($product->getStoreId())
            ->addAttributeToFilter('entity_id', array('in' => $childProductIds))
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getSearchableAttributes()->getColumnValues('attribute_code'));

        return $childProductCollection;
    }

    /**
     * @param $emptyIndex
     * @param $productCollection
     * @return mixed
     */
    protected function _indexProductCollection($emptyIndex, $productCollection)
    {
        $combinedProductData = array();
        $idsForDeletion = array();
        $storeId = $productCollection->getStoreId();

        foreach ($productCollection as $product) {
            /** @var Mage_Catalog_Model_Product $product */

            $product->setStoreId($storeId);

            if ($this->_canIndexProduct($product)) {
                $combinedProductData[] = $this->_getProductData($product);
            } else {
                $idsForDeletion[] = $this->_getSolrId($product);
            }
        }

        if (!$emptyIndex && sizeof($idsForDeletion)) {
            $this->getResource()->deleteByMultipleIds($storeId, $idsForDeletion);
        }

        if (sizeof($combinedProductData)) {
            $this->getResource()->addDocuments($storeId, $combinedProductData);
            return $storeId;
        }
        return $storeId;
    }
}