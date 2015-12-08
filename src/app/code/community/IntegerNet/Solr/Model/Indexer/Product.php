<?php
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\AttributeRepository;
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
    /**
     * Configuration reader, by store id
     *
     * @var  Config[]
     */
    protected $_config;
    /** @var  EventDispatcher */
    protected $_eventDispatcher;
    /** @var  AttributeRepository */
    protected $_attributeRepository;

    /** @var  IntegerNet_Solr_Model_Indexer_Product_Renderer */
    protected $_renderer;
    /** @var  IntegerNet_Solr_Model_Indexer_Category_Repository */
    protected $_categoryRepository;
    /** @var  IntegerNet_Solr_Model_Indexer_Product_Repository */
    protected $_productRepository;

    /**
     * @var ResourceFacade
     */
    protected $_resource;

    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->_resource = Mage::helper('integernet_solr/factory')->getSolrResource();
        $this->_config = Mage::helper('integernet_solr/factory')->getStoreConfig();
        $this->_attributeRepository = Mage::helper('integernet_solr');

        $this->_eventDispatcher = Mage::helper('integernet_solr');
        $this->_renderer = Mage::getModel('integernet_solr/indexer_product_renderer');
        $this->_categoryRepository = Mage::getModel('integernet_solr/indexer_category_repository');
        $this->_productRepository = Mage::getModel('integernet_solr/indexer_product_repository');
    }

    protected function _getStoreConfig($storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore(true)->getId();
        }
        $storeId = (int)$storeId;
        if (!isset($this->_config[$storeId])) {
            throw new Exception("Store with ID {$storeId} not found.");
        }
        return $this->_config[$storeId];
    }

    /**
     * @param array|null $productIds Restrict to given Products if this is set
     * @param boolean|string $emptyIndex Whether to truncate the index before refilling it
     * @param null|Mage_Core_Model_Store $restrictToStore
     */
    public function reindex($productIds = null, $emptyIndex = false, $restrictToStore = null)
    {
        if (is_null($productIds)) {
            $this->getResource()->checkSwapCoresConfiguration($restrictToStore === null ? null : $restrictToStore->getId());
        }

        $pageSize = intval($this->_getStoreConfig()->getIndexingConfig()->getPagesize());
        if ($pageSize <= 0) {
            $pageSize = 100;
        }

        foreach($this->_config as $storeId => $storeConfig) {
            if (!is_null($restrictToStore) && ($restrictToStore->getId() != $storeId)) {
                continue;
            }

            if (!$storeConfig->getGeneralConfig()->isActive()) {
                continue;
            }

            if (is_null($productIds) && $storeConfig->getIndexingConfig()->isSwapCores()) {
                $this->getResource()->setUseSwapIndex();
            }

            if (
                ($emptyIndex && $storeConfig->getIndexingConfig()->isDeleteDocumentsBeforeIndexing())
                || $emptyIndex === 'force'
            ) {
                $this->getResource()->deleteAllDocuments($storeId);
            }

            //TODO move pagination logic to repository
            $pageNumber = 1;
            do {
                $productCollection = $this->_getProductCollection($storeId, $productIds, $pageSize, $pageNumber++);

                $this->_indexProductCollection($emptyIndex, $productCollection);

            } while ($pageNumber <= $productCollection->getLastPageNumber());

            $this->getResource()->setUseSwapIndex(false);
        }

        $this->_renderer->stopStoreEmulation();

        if (is_null($productIds)) {
            $this->getResource()->swapCores($restrictToStore === null ? null : $restrictToStore->getId());
        }
    }

    /**
     * @param string[] $productIds
     */
    public function deleteIndex($productIds)
    {
        foreach($this->_config as $storeId => $storeConfig) {

            if (! $storeConfig->getGeneralConfig()->isActive()) {
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
        $categoryIds = $this->_categoryRepository->getCategoryIds($product);
        $productData = new Varien_Object(array(
            'id' => $this->_getSolrId($product), // primary identifier, must be unique
            'product_id' => $product->getId(),
            'category' => $categoryIds, // @todo get category ids from parent anchor categories as well
            'category_name_s_mv' => $this->_categoryRepository->getCategoryNames($categoryIds, $product->getStoreId()),
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

        $this->_eventDispatcher->dispatch('integernet_solr_get_product_data', array('product' => $product, 'product_data' => $productData));

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
        return $this->_productRepository->getProductCollection($storeId, $productIds, $pageSize, $pageNumber);
    }


    /**
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    protected function _canIndexProduct($product)
    {
        $this->_eventDispatcher->dispatch('integernet_solr_can_index_product', array('product' => $product));

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
        foreach ($this->_attributeRepository->getFilterableInCatalogOrSearchAttributes() as $attribute) {
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

        foreach ($this->_attributeRepository->getSearchableAttributes() as $attribute) {

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
        $useHtmlForResults = $this->_getStoreConfig($product->getStoreId())->getResultsConfig()->isUseHtmlFromSolr();
        $this->_renderer->addResultHtmlToProductData($product, $productData, $useHtmlForResults);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $productData
     */
    protected function _addCategoryProductPositionsToProductData($product, $productData)
    {
        foreach($this->_categoryRepository->getCategoryPositions($product) as $positionRow) {
            $productData['category_' . $positionRow['category_id'] . '_position_i'] = $positionRow['position'];
        }
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
     * @return ResourceFacade
     */
    public function getResource()
    {
        return $this->_getResource();
    }
    /**
     * Retrieve model resource
     *
     * @return ResourceFacade
     */
    protected function _getResource()
    {
        return $this->_resource;
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
            ->addAttributeToSelect($this->_attributeRepository->getAttributeCodesToIndex());

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

}