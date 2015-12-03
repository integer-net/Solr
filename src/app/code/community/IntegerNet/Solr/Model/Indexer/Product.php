<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Indexer_Product extends Mage_Core_Model_Abstract
{
    /** @var IntegerNet_Solr_Block_Indexer_Item[] */
    protected $_itemBlocks = array();

    protected $_resourceName = 'integernet_solr/solr';

    protected $_pathCategoryIds = array();

    protected $_excludedCategoryIds = array();

    protected $_currentStoreId = null;

    protected $_categoryNames = array();

    protected $_initialEnvironmentInfo = null;

    protected $_isEmulated = false;

    /**
     * @param array|null $productIds Restrict to given Products if this is set
     * @param boolean|string $emptyIndex Whether to truncate the index before refilling it
     * @param null|Mage_Core_Model_Store $restrictToStore
     */
    public function reindex($productIds = null, $emptyIndex = false, $restrictToStore = null)
    {
        if (is_null($productIds)) {
            $this->getResource()->checkSwapCoresConfiguration($restrictToStore);
        }
        
        $pageSize = intval(Mage::getStoreConfig('integernet_solr/indexing/pagesize'));
        if ($pageSize <= 0) {
            $pageSize = 100;
        }

        foreach(Mage::app()->getStores() as $store) {
            /** @var Mage_Core_Model_Store $store */

            $storeId = $store->getId();

            if (!is_null($restrictToStore) && ($restrictToStore->getId() != $storeId)) {
                continue;
            }

            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
                continue;
            }

            if (!$store->getIsActive()) {
                continue;
            }

            if (is_null($productIds) && Mage::getStoreConfigFlag('integernet_solr/indexing/swap_cores', $storeId)) {
                $this->getResource()->setUseSwapIndex();
            }

            if (
                ($emptyIndex && Mage::getStoreConfigFlag('integernet_solr/indexing/delete_documents_before_indexing', $storeId))
                || $emptyIndex === 'force'
            ) {
                $this->getResource()->deleteAllDocuments($storeId);
            }

            $pageNumber = 1;
            do {
                $productCollection = $this->_getProductCollection($storeId, $productIds, $pageSize, $pageNumber++);

                $this->_indexProductCollection($emptyIndex, $productCollection);

            } while ($pageNumber <= $productCollection->getLastPageNumber());

            $this->getResource()->setUseSwapIndex(false);
        }

        $this->_stopStoreEmulation();

        if (is_null($productIds)) {
            $this->getResource()->swapCores($restrictToStore);
        }
    }
    
    protected function _swapCores()
    {
        
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
        $categoryIds = $this->_getCategoryIds($product);
        $productData = new Varien_Object(array(
            'id' => $this->_getSolrId($product), // primary identifier, must be unique
            'product_id' => $product->getId(),
            'category' => $categoryIds, // @todo get category ids from parent anchor categories as well
            'category_name_s_mv' => $this->_getCategoryNames($categoryIds, $product->getStoreId()),
            'category_name_s_mv_boost' => 2,
            'store_id' => $product->getStoreId(),
            'content_type' => 'product',
            'is_visible_in_catalog_i' => intval(in_array($product->getVisibility(), Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds())),
            'is_visible_in_search_i' => intval(in_array($product->getVisibility(), Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds())),
        ));

        $this->_addBoostToProductData($product, $productData);

        $this->_addFacetsToProductData($product, $productData);

        $this->_addSearchDataToProductData($product, $productData);

        $this->_addResultHtmlToProductData($product, $productData);

        $this->_addCategoryProductPositionsToProductData($product, $productData);

        Mage::dispatchEvent('integernet_solr_get_product_data', array('product' => $product, 'product_data' => $productData));

        return $productData->getData();
    }

    /**
     * @param int $storeId
     * @param int[]|null $productIds
     * @param int $pageSize
     * @param int $pageNumber
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _getProductCollection($storeId, $productIds = null, $pageSize = null, $pageNumber = 0)
    {
        Mage::app()->getStore($storeId)->setConfig('catalog/frontend/flat_catalog_product', 0);

        /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId($storeId)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addAttributeToSelect(array('visibility', 'status', 'url_key', 'solr_boost', 'solr_exclude'))
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getSearchableAttributes()->getColumnValues('attribute_code'))
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getFilterableInCatalogOrSearchAttributes()->getColumnValues('attribute_code'));
            
        if (is_array($productIds)) {
            $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
        }

        if (!is_null($pageSize)) {
            $productCollection->setPageSize($pageSize);
            $productCollection->setCurPage($pageNumber);
        }

        Mage::dispatchEvent('integernet_solr_product_collection_load_before', array(
            'collection' => $productCollection
        ));

        $event = new Varien_Event();
        $event->setCollection($productCollection);
        $observer = new Varien_Event_Observer();
        $observer->setEvent($event);

        Mage::getModel('tax/observer')->addTaxPercentToProductCollection($observer);

        Mage::dispatchEvent('integernet_solr_product_collection_load_after', array(
            'collection' => $productCollection
        ));

        return $productCollection;
    }


    /**
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    protected function _canIndexProduct($product)
    {
        Mage::dispatchEvent('integernet_solr_can_index_product', array('product' => $product));

        if ($product->getSolrExclude()) {
            return false;
        }
        if ($product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            return false;
        }
        if (!in_array($product->getVisibility(), Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds())) {
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
        foreach (Mage::helper('integernet_solr')->getFilterableInCatalogOrSearchAttributes() as $attribute) {

            if (!$product->getData($attribute->getAttributeCode())) {
                continue;
            }
            
            switch ($attribute->getFrontendInput()) {
                case 'select':
                    $rawValue = $product->getData($attribute->getAttributeCode());
                    if ($rawValue && $this->_isInteger($rawValue)) {
                        $productData->setData($attribute->getAttributeCode() . '_facet', $rawValue);
                    }
                    break;
                case 'multiselect':
                    $rawValue = $product->getData($attribute->getAttributeCode());
                    if ($rawValue && $this->_isInteger($rawValue)) {
                        $productData->setData($attribute->getAttributeCode() . '_facet', explode(',', $rawValue));
                    }
                    break;
            }

            $fieldName = Mage::helper('integernet_solr')->getFieldName($attribute);
            if (!$productData->hasData($fieldName)) {
                $value = trim(strip_tags($attribute->getFrontend()->getValue($product)));
                if (!empty($value)) {
                    if ($attribute->getFrontendInput() == 'multiselect') {
                        $value = array_map('trim', explode(',', $value));
                    }
                    $productData->setData($fieldName, $value);

                    if (strstr($fieldName, '_t') == true && $attribute->getUsedForSortBy()) {
                        $productData->setData(
                            Mage::helper('integernet_solr')->getFieldName($attribute, true),
                            $value
                        );
                    }
                }
            }

            if ($attribute->getAttributeCode() == 'price') {
                $price = $product->getFinalPrice();
                if ($price == 0) {
                    $price = $product->getMinimalPrice();
                }
                $price = Mage::helper('tax')->getPrice($product, $price, null, null, null, null, $product->getStoreId());
                $productData->setData('price_f', floatval($price));
            }
        }
    }

    /**
     * The schema expected for facet attributes integer values
     *
     * @param string $rawValue
     * @return bool
     */
    protected function _isInteger($rawValue)
    {
        $rawValues = explode(',', $rawValue);

        foreach ($rawValues as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $productData
     */
    protected function _addSearchDataToProductData($product, $productData)
    {
        $hasChildProducts = true;
        try {
            $childProducts = $this->_getChildProductsCollection($product);
        } catch (Exception $e) {
            $hasChildProducts = false;
        }

        if (!$productData->getData('price_f')) {
            $productData->setData('price_f', 0.00);
        }

        foreach (Mage::helper('integernet_solr')->getSearchableAttributes() as $attribute) {

            if (get_class($attribute->getSource()) == 'Mage_Eav_Model_Entity_Attribute_Source_Boolean') {
                continue;
            }

            if (($attribute->getAttributeCode() == 'price') && ($productData->getData('price_f') > 0)) {
                continue;
            }

            $fieldName = Mage::helper('integernet_solr')->getFieldName($attribute);

            $solrBoost = floatval($attribute->getSolrBoost());
            if ($solrBoost != 1) {
                $productData->setData($fieldName . '_boost', $solrBoost);
            }

            $attribute->setStoreId($product->getStoreId());

            if ($product->getData($attribute->getAttributeCode())
                && $value = trim(strip_tags($attribute->getFrontend()->getValue($product)))
            ) {
                if ($attribute->getFrontendInput() == 'multiselect') {
                    $value = array_map('trim', explode(',', $value));
                }
                $productData->setData($fieldName, $value);

                if (strstr($fieldName, '_t') == true && $attribute->getUsedForSortBy()) {
                    $productData->setData(
                        Mage::helper('integernet_solr')->getFieldName($attribute, true),
                        $value
                    );
                }
            }

            if ($hasChildProducts && $attribute->getBackendType() != 'decimal') {

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

        if (!$productData->getData('price_f')) {
            $productData->setData('price_f', 0.00);
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $productData
     */
    protected function _addResultHtmlToProductData($product, $productData)
    {
        $storeId = $product->getStoreId();
        if ($this->_currentStoreId != $storeId) {

            $this->_emulateStore($storeId);
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
        if (@class_exists(Mage::getConfig()->getBlockClassName($priceBlockType)) && Mage::app()->getLayout()->createBlock($priceBlockType)) {

            $block->addPriceBlockType('simple', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('virtual', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('grouped', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('downloadable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('configurable', $priceBlockType, 'catalog/product/price.phtml');
            $block->addPriceBlockType('bundle', 'germansetup/bundle_catalog_product_price', 'bundle/catalog/product/price.phtml');
        }

        $priceBlockType = 'magesetup/catalog_product_price';
        if (@class_exists(Mage::getConfig()->getBlockClassName($priceBlockType)) && Mage::app()->getLayout()->createBlock($priceBlockType)) {

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

        $storeId = $product->getStoreId();
        if (!isset($this->_pathCategoryIds[$storeId])) {
            $this->_pathCategoryIds[$storeId] = array();
        }
        $lookupCategoryIds = array_diff($categoryIds, array_keys($this->_pathCategoryIds[$storeId]));
        $this->_lookupCategoryIdPaths($lookupCategoryIds, $storeId);

        $foundCategoryIds = array();
        foreach($categoryIds as $categoryId) {
            $categoryPathIds = $this->_pathCategoryIds[$storeId][$categoryId];
            $foundCategoryIds = array_merge($foundCategoryIds, $categoryPathIds);
        }

        $foundCategoryIds = array_unique($foundCategoryIds);

        $foundCategoryIds = array_diff($foundCategoryIds, $this->_getExcludedCategoryIds($storeId));

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
                $this->_pathCategoryIds[$storeId][$category->getId()] = array();
                continue;
            }

            $categoryPathIds = explode('/', $category->getPath());
            if (!in_array(Mage::app()->getStore($storeId)->getGroup()->getRootCategoryId(), $categoryPathIds)) {
                $this->_pathCategoryIds[$storeId][$category->getId()] = array();
                continue;
            }

            array_shift($categoryPathIds);
            array_shift($categoryPathIds);
            $this->_pathCategoryIds[$storeId][$category->getId()] = $categoryPathIds;
        }
    }

    /**
     * @param int $storeId
     * @return array
     */
    protected function _getExcludedCategoryIds($storeId)
    {
        if (!isset($this->_excludedCategoryIds[$storeId])) {

            // exclude categories which are configured as excluded
            /** @var $excludedCategories Mage_Catalog_Model_Resource_Category_Collection */
            $excludedCategories = Mage::getResourceModel('catalog/category_collection')
                ->addFieldToFilter('solr_exclude', 1);

            $this->_excludedCategoryIds[$storeId] = $excludedCategories->getAllIds();

            // exclude children of categories which are configured as "children excluded"
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
                $this->_excludedCategoryIds[$storeId] = array_merge($this->_excludedCategoryIds[$storeId], $excludedChildrenCategories->getAllIds());
            }
        }

        return $this->_excludedCategoryIds[$storeId];
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $productData
     */
    protected function _addCategoryProductPositionsToProductData($product, $productData)
    {
        foreach($this->getCategoryPositions($product) as $positionRow) {
            $productData['category_' . $positionRow['category_id'] . '_position_i'] = $positionRow['position'];
        }
    }

    /**
     * Retrieve product category identifiers
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getCategoryPositions($product)
    {
        /** @var $setup Mage_Catalog_Model_Resource_Setup */
        $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
        $adapter = Mage::getSingleton('core/resource')->getConnection('catalog_read');

        $select = $adapter->select()
            ->from($setup->getTable('catalog/category_product_index'), array('category_id', 'position'))
            ->where('product_id = ?', (int)$product->getId())
            ->where('store_id = ?', $product->getStoreId());

        return $adapter->fetchAll($select);
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
            } else {
                $productData->setData('_boost', 1);
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

        if (sizeof($childProductIds) && is_array(current($childProductIds))) {
            $childProductIds = current($childProductIds);
        }

        if (!sizeof($childProductIds)) {
            Mage::throwException('Product ' . $product->getSku() . ' doesn\'t have any child products.');
        }

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
     * @param boolean $emptyIndex
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     * @return int
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

    /**
     * @param $categoryIds
     * @param $storeId
     * @return array
     */
    protected function _getCategoryNames($categoryIds, $storeId)
    {
        $categoryNames = array();

        /** @var Mage_Catalog_Model_Resource_Category $categoryResource */
        $categoryResource = Mage::getResourceModel('catalog/category');
        foreach($categoryIds as $key => $categoryId) {
            if (!isset($this->_categoryNames[$storeId][$categoryId])) {
                $this->_categoryNames[$storeId][$categoryId] = $categoryResource->getAttributeRawValue($categoryId, 'name', $storeId);
            }
            $categoryNames[] = $this->_categoryNames[$storeId][$categoryId];
        }
        return $categoryNames;
    }

    /**
     * @param int $storeId
     * @throws Mage_Core_Exception
     */
    protected function _emulateStore($storeId)
    {
        $newLocaleCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId);
        Mage::app()->getLocale()->setLocaleCode($newLocaleCode);
        Mage::getSingleton('core/translate')->setLocale($newLocaleCode)->init(Mage_Core_Model_App_Area::AREA_FRONTEND, true);
        $this->_currentStoreId = $storeId;
        $this->_initialEnvironmentInfo = Mage::getSingleton('core/app_emulation')->startEnvironmentEmulation($storeId);
        $this->_isEmulated = true;
        Mage::getDesign()->setStore($storeId);
        Mage::getDesign()->setPackageName();
        $themeName = Mage::getStoreConfig('design/theme/default', $storeId);
        Mage::getDesign()->setTheme($themeName);
    }

    protected function _stopStoreEmulation()
    {
        if ($this->_isEmulated && $this->_initialEnvironmentInfo) {
            Mage::getSingleton('core/app_emulation')->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
        }
    }
}