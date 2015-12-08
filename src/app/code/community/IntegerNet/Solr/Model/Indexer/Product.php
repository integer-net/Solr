<?php
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\Product;
use IntegerNet\Solr\Implementor\ProductIterator;
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
    /** @var  IntegerNet_Solr_Model_Bridge_CategoryRepository */
    protected $_categoryRepository;
    /** @var  IntegerNet_Solr_Model_Bridge_ProductRepository */
    protected $_productRepository;

    /** @var  IntegerNet_Solr_Model_Indexer_Product_Renderer */
    protected $_renderer;

    /**
     * @var ResourceFacade
     */
    protected $_resource;

    protected function _construct()
    {
        $this->_resource = Mage::helper('integernet_solr/factory')->getSolrResource();
        $this->_config = Mage::helper('integernet_solr/factory')->getStoreConfig();

        $this->_eventDispatcher = Mage::helper('integernet_solr');
        $this->_renderer = Mage::getModel('integernet_solr/indexer_product_renderer');
        $this->_attributeRepository = Mage::getSingleton('integernet_solr/bridge_attributeRepository');
        $this->_categoryRepository = Mage::getModel('integernet_solr/bridge_categoryRepository');
        $this->_productRepository = Mage::getModel('integernet_solr/bridge_productRepository');
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

            $productCollection = $this->_productRepository->setPageSizeForIndex($pageSize)->getProductsForIndex($storeId, $productIds);
            $this->_indexProductCollection($emptyIndex, $productCollection, $storeId);

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
     * @param Product $product
     * @return array
     */
    protected function _getProductData(Product $product)
    {
        $categoryIds = $this->_categoryRepository->getCategoryIds($product);
        $productData = new Varien_Object(array(
            'id' => $product->getSolrId(), // primary identifier, must be unique
            'product_id' => $product->getId(),
            'category' => $categoryIds, // @todo get category ids from parent anchor categories as well
            'category_name_s_mv' => $this->_categoryRepository->getCategoryNames($categoryIds, $product->getStoreId()),
            'category_name_s_mv_boost' => 2,
            'store_id' => $product->getStoreId(),
            'content_type' => 'product',
            'is_visible_in_catalog_i' => $product->isVisibleInCatalog(),
            'is_visible_in_search_i' => $product->isVisibleInSearch(),
        ));

        $this->_addBoostToProductData($product, $productData);

        $this->_addFacetsToProductData($product, $productData);

        $this->_addSearchDataToProductData($product, $productData);

        $this->_addResultHtmlToProductData($product, $productData);

        $this->_addCategoryProductPositionsToProductData($product, $productData);

        //TODO try to eliminate Magento product dependency without breaking observer compatibility
        $this->_eventDispatcher->dispatch('integernet_solr_get_product_data', array('product' => $product->getMagentoProduct(), 'product_data' => $productData));

        return $productData->getData();
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
     * @param Product $product
     * @param Varien_Object $productData
     */
    protected function _addFacetsToProductData($product, $productData)
    {
        foreach ($this->_attributeRepository->getFilterableInCatalogOrSearchAttributes() as $attribute) {
            switch ($attribute->getFacetType()) {
                case Attribute::FACET_TYPE_SELECT:
                    $rawValue = $product->getAttributeValue($attribute);
                    if ($rawValue && $this->_isInteger($rawValue)) {
                        $productData->setData($attribute->getAttributeCode() . '_facet', $rawValue);
                    }
                    break;
                case Attribute::FACET_TYPE_MULTISELECT:
                    $rawValue = $product->getAttributeValue($attribute);
                    if ($rawValue && $this->_isInteger($rawValue)) {
                        $productData->setData($attribute->getAttributeCode() . '_facet', explode(',', $rawValue));
                    }
                    break;
            }

            $fieldName = Mage::helper('integernet_solr')->getFieldName($attribute);
            if (!$productData->hasData($fieldName)) {
                $value = $product->getSearchableAttributeValue($attribute);
                if (!empty($value)) {
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
                $price = $product->getPrice();
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
     * @param Product $product
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

            $attribute->setStoreId($product->getStoreId()); //TODO (re)move

            if ($product->getAttributeValue($attribute)
                && $value = $product->getSearchableAttributeValue($attribute)
            ) {
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
                    /** @var $childProduct Product */
                    if ($childProduct->getAttributeValue($attribute)
                        && $childValue = $childProduct->getSearchableAttributeValue($attribute)
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
     * @todo try to remove Magento product dependency
     * @param IntegerNet_Solr_Model_Bridge_Product $product
     * @param Varien_Object $productData
     */
    protected function _addResultHtmlToProductData(IntegerNet_Solr_Model_Bridge_Product $product, $productData)
    {
        $useHtmlForResults = $this->_getStoreConfig($product->getStoreId())->getResultsConfig()->isUseHtmlFromSolr();
        $this->_renderer->addResultHtmlToProductData($product->getMagentoProduct(), $productData, $useHtmlForResults);
    }

    /**
     * @param Product $product
     * @param Varien_Object $productData
     */
    protected function _addCategoryProductPositionsToProductData($product, $productData)
    {
        foreach($this->_categoryRepository->getCategoryPositions($product) as $positionRow) {
            $productData['category_' . $positionRow['category_id'] . '_position_i'] = $positionRow['position'];
        }
    }


    /**
     * @param Product $product
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
     * @param Product $product
     * @return ProductIterator
     */
    protected function _getChildProductsCollection($product)
    {
        return $product->getChildren();
    }

    /**
     * @param boolean $emptyIndex
     * @param \IntegerNet\Solr\Implementor\ProductIterator $productCollection
     * @return int
     */
    protected function _indexProductCollection($emptyIndex, $productCollection, $storeId)
    {
        $combinedProductData = array();
        $idsForDeletion = array();

        foreach ($productCollection as $product) {
            if ($product->isIndexable()) {
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