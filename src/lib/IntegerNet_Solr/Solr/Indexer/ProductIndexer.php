<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\Solr\Indexer;
use IntegerNet\Solr\Implementor\ProductRenderer;
use IntegerNet\Solr\Implementor\StoreEmulation;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\Product;
use IntegerNet\Solr\Implementor\ProductIterator;
use IntegerNet\Solr\Implementor\ProductRepository;
use IntegerNet\Solr\Implementor\IndexCategoryRepository;

class ProductIndexer
{
    const CONTENT_TYPE = 'product';
    
    /** @var  int */
    private $_defaultStoreId;
    /**
     * Configuration reader, by store id
     *
     * @var  Config[]
     */
    private $_config;
    /** @var  ResourceFacade */
    private $_resource;
    /** @var  EventDispatcher */
    private $_eventDispatcher;
    /** @var  AttributeRepository */
    private $_attributeRepository;
    /** @var  CategoryRepository */
    private $_categoryRepository;
    /** @var  ProductRepository */
    private $_productRepository;
    /** @var  ProductRenderer */
    private $_renderer;
    /** @var StoreEmulation */
    private $storeEmulation;

    /**
     * @param int $defaultStoreId
     * @param Config[] $_config
     * @param ResourceFacade $_resource
     * @param EventDispatcher $_eventDispatcher
     * @param AttributeRepository $_attributeRepository
     * @param IndexCategoryRepository $_categoryRepository
     * @param ProductRepository $_productRepository
     * @param ProductRenderer $_renderer
     * @param StoreEmulation $storeEmulation
     */
    public function __construct($defaultStoreId, array $_config, ResourceFacade $_resource, EventDispatcher $_eventDispatcher,
                                AttributeRepository $_attributeRepository, IndexCategoryRepository $_categoryRepository,
                                ProductRepository $_productRepository, ProductRenderer $_renderer, StoreEmulation $storeEmulation)
    {
        $this->_defaultStoreId = $defaultStoreId;
        $this->_config = $_config;
        $this->_resource = $_resource;
        $this->_eventDispatcher = $_eventDispatcher;
        $this->_attributeRepository = $_attributeRepository;
        $this->_categoryRepository = $_categoryRepository;
        $this->_productRepository = $_productRepository;
        $this->_renderer = $_renderer;
        $this->storeEmulation = $storeEmulation;
    }

    protected function _getStoreConfig($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->_defaultStoreId;
        }
        $storeId = (int)$storeId;
        if (!isset($this->_config[$storeId])) {
            throw new \Exception("Store with ID {$storeId} not found.");
        }
        return $this->_config[$storeId];
    }

    /**
     * @param array|null $productIds Restrict to given Products if this is set
     * @param boolean|string $emptyIndex Whether to truncate the index before refilling it
     * @param null|\Mage_Core_Model_Store $restrictToStore
     * @throws \Exception
     * @throws \IntegerNet\Solr\Exception
     */
    public function reindex($productIds = null, $emptyIndex = false, $restrictToStore = null)
    {
        if (is_null($productIds)) {
            $this->_getResource()->checkSwapCoresConfiguration($restrictToStore === null ? null : $restrictToStore->getId());
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
            $this->storeEmulation->start($storeId);
            try {

                if (is_null($productIds) && $storeConfig->getIndexingConfig()->isSwapCores()) {
                    $this->_getResource()->setUseSwapIndex();
                }

                if (
                    ($emptyIndex && $storeConfig->getIndexingConfig()->isDeleteDocumentsBeforeIndexing())
                    || $emptyIndex === 'force'
                ) {
                    $this->_getResource()->deleteAllDocuments($storeId, self::CONTENT_TYPE);
                }

                $productCollection = $this->_productRepository->setPageSizeForIndex($pageSize)->getProductsForIndex($storeId, $productIds);
                $this->_indexProductCollection($emptyIndex, $productCollection, $storeId);

                $this->_getResource()->setUseSwapIndex(false);
            } catch (\Exception $e) {
                $this->storeEmulation->stop();
                throw $e;
            }
            $this->storeEmulation->stop();
        }

        if (is_null($productIds)) {
            $this->_getResource()->swapCores($restrictToStore === null ? null : $restrictToStore->getId());
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

            $this->_getResource()->deleteByMultipleIds($storeId, $ids);
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
        $productData = new IndexDocument(array(
            'id' => $product->getSolrId(), // primary identifier, must be unique
            'product_id' => $product->getId(),
            'category' => $categoryIds, // @todo get category ids from parent anchor categories as well
            'category_name_t_mv' => $this->_categoryRepository->getCategoryNames($categoryIds, $product->getStoreId()),
            'store_id' => $product->getStoreId(),
            'content_type' => self::CONTENT_TYPE,
            'is_visible_in_catalog_i' => $product->isVisibleInCatalog(),
            'is_visible_in_search_i' => $product->isVisibleInSearch(),
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
     * @param IndexDocument $productData
     */
    protected function _addFacetsToProductData(Product $product, IndexDocument $productData)
    {
        foreach ($this->_attributeRepository->getFilterableInCatalogOrSearchAttributes($product->getStoreId()) as $attribute) {
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

            $indexField = new IndexField($attribute);
            $fieldName = $indexField->getFieldName();
            if (!$productData->hasData($fieldName)) {
                $value = $product->getSearchableAttributeValue($attribute);
                if (!empty($value)) {
                    $productData->setData($fieldName, $value);

                    if (strstr($fieldName, '_t') == true && $attribute->getUsedForSortBy()) {
                        $productData->setData(
                            $indexField->forSorting()->getFieldName(),
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
     * @param IndexDocument $productData
     */
    protected function _addSearchDataToProductData(Product $product, IndexDocument $productData)
    {
        $hasChildProducts = true;
        try {
            $childProducts = $this->_getChildProductsCollection($product);
        } catch (\Exception $e) {
            $hasChildProducts = false;
        }

        if (!$productData->getData('price_f')) {
            $productData->setData('price_f', 0.00);
        }

        foreach ($this->_attributeRepository->getSearchableAttributes($product->getStoreId()) as $attribute) {

            if (($attribute->getAttributeCode() == 'price') && ($productData->getData('price_f') > 0)) {
                continue;
            }

            $indexField = new IndexField($attribute);
            $fieldName = $indexField->getFieldName();

            $solrBoost = floatval($attribute->getSolrBoost());
            if ($solrBoost != 1) {
                $productData->setData($fieldName . '_boost', $solrBoost);
            }

            if ($product->getAttributeValue($attribute)
                && $value = $product->getSearchableAttributeValue($attribute)
            ) {
                $productData->setData($fieldName, $value);

                if (strstr($fieldName, '_t') == true && $attribute->getUsedForSortBy()) {
                    $productData->setData(
                        $indexField->forSorting()->getFieldName(),
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
     * @param Product $product
     * @param IndexDocument $productData
     */
    protected function _addResultHtmlToProductData(Product $product, IndexDocument $productData)
    {
        $useHtmlForResults = $this->_getStoreConfig($product->getStoreId())->getResultsConfig()->isUseHtmlFromSolr();
        $this->_renderer->addResultHtmlToProductData($product, $productData, $useHtmlForResults);
    }

    /**
     * @param Product $product
     * @param IndexDocument $productData
     */
    protected function _addCategoryProductPositionsToProductData(Product $product, IndexDocument $productData)
    {
        foreach($this->_categoryRepository->getCategoryPositions($product) as $positionRow) {
            $productData['category_' . $positionRow['category_id'] . '_position_i'] = $positionRow['position'];
        }
    }


    /**
     * @param Product $product
     * @param IndexDocument $productData
     */
    protected function _addBoostToProductData(Product $product, IndexDocument $productData)
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
     * @param int $storeId
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
            $this->_getResource()->deleteByMultipleIds($storeId, $idsForDeletion);
        }

        if (sizeof($combinedProductData)) {
            $this->_getResource()->addDocuments($storeId, $combinedProductData);
            return $storeId;
        }
        return $storeId;
    }


}