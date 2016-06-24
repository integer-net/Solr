<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Indexer;
use IntegerNet\Solr\Implementor\StoreEmulation;
use IntegerNet\SolrCategories\Implementor\CategoryRenderer;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrCategories\Implementor\Category;
use IntegerNet\SolrCategories\Implementor\CategoryIterator;
use IntegerNet\SolrCategories\Implementor\CategoryRepository;
use IntegerNet\Solr\Indexer\IndexDocument;

class CategoryIndexer
{
    const CONTENT_TYPE = 'category';
    
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
    /** @var  CategoryRepository */
    private $_categoryRepository;
    /** @var  CategoryRenderer */
    private $_renderer;
    /** @var StoreEmulation */
    private $storeEmulation;

    /**
     * @param int $defaultStoreId
     * @param Config[] $_config
     * @param ResourceFacade $_resource
     * @param EventDispatcher $_eventDispatcher
     * @param CategoryRepository $_categoryRepository
     * @param CategoryRenderer $_renderer
     * @param StoreEmulation $storeEmulation
     */
    public function __construct($defaultStoreId, array $_config, ResourceFacade $_resource, EventDispatcher $_eventDispatcher,
                                CategoryRepository $_categoryRepository, CategoryRenderer $_renderer, StoreEmulation $storeEmulation)
    {
        $this->_defaultStoreId = $defaultStoreId;
        $this->_config = $_config;
        $this->_resource = $_resource;
        $this->_eventDispatcher = $_eventDispatcher;
        $this->_categoryRepository = $_categoryRepository;
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
     * @param array|null $categoryIds Restrict to given Categories if this is set
     * @param boolean|string $emptyIndex Whether to truncate the index before refilling it
     * @param null|int[]
     * @throws \Exception
     */
    public function reindex($categoryIds = null, $emptyIndex = false, $restrictToStoreIds = null)
    {
        foreach($this->_config as $storeId => $storeConfig) {

            if (!$storeConfig->getGeneralConfig()->isActive()) {
                continue;
            }

            if (!$storeConfig->getCategoryConfig()->isIndexerActive()) { 
                continue;
            }

            if (!is_null($restrictToStoreIds) && !in_array($storeId, $restrictToStoreIds)) {
                continue;
            }

            $this->storeEmulation->start($storeId);
            try {

                if (
                    ($emptyIndex && $storeConfig->getIndexingConfig()->isDeleteDocumentsBeforeIndexing())
                    || $emptyIndex === 'force'
                ) {
                    $this->_getResource()->deleteAllDocuments($storeId, self::CONTENT_TYPE);
                }

                $pageSize = intval($storeConfig->getIndexingConfig()->getPagesize());
                if ($pageSize <= 0) {
                    $pageSize = 100;
                }

                $categoryCollection = $this->_categoryRepository->setPageSizeForIndex($pageSize)->getCategoriesForIndex($storeId, $categoryIds);
                $this->_indexCategoryCollection($emptyIndex, $categoryCollection, $storeId);

            } catch (\Exception $e) {
                $this->storeEmulation->stop();
                throw $e;
            }
            $this->storeEmulation->stop();
        }
    }

    /**
     * @param string[] $categoryIds
     */
    public function deleteIndex($categoryIds)
    {
        foreach($this->_config as $storeId => $storeConfig) {

            if (! $storeConfig->getGeneralConfig()->isActive()) {
                continue;
            }

            $ids = array();

            foreach($categoryIds as $categoryId) {
                $ids[] = 'category_' . $categoryId . '_' . $storeId;
            }

            $this->_getResource()->deleteByMultipleIds($storeId, $ids);
        }
    }


    /**
     * Generate single category data for Solr
     *
     * @param Category $category
     * @return array
     */
    protected function _getCategoryData(Category $category)
    {
        $categoryData = new IndexDocument(array(
            'id' => $category->getSolrId(), // primary identifier, must be unique
            'product_id' => $category->getId(),
            'store_id' => $category->getStoreId(),
            'content_type' => self::CONTENT_TYPE,
        ));

        $this->_addSearchDataToCategoryData($category, $categoryData);

        $this->_addBoostToCategoryData($category, $categoryData);

        $this->_eventDispatcher->dispatch('integernet_solr_get_category_data', array('category' => $category, 'category_data' => $categoryData));

        return $categoryData->getData();
    }

    /**
     * Get unique identifier for Solr
     *
     * @param \IntegerNet\SolrCategories\Implementor\Category $category
     * @return string
     */
    protected function _getSolrId($category)
    {
        return 'category_' . $category->getId() . '_' . $category->getStoreId();
    }

    /**
     * @param Category $category
     * @param IndexDocument $categoryData
     */
    protected function _addSearchDataToCategoryData(Category $category, IndexDocument $categoryData)
    {
        $fieldName = 'name';

        $solrBoost = 5; /** @todo get correct value */
        if ($solrBoost != 1) {
            $categoryData->setData($fieldName . '_boost', $solrBoost);
        }

        if ($value = $category->getName()) {
            $categoryData->setData($fieldName . '_t', $value);
        }
        
        $fieldName = 'description';

        $solrBoost = 1; /** @todo get correct value */
        if ($solrBoost != 1) {
            $categoryData->setData($fieldName . '_boost', $solrBoost);
        }

        if ($value = str_replace(array("\n", "\r"), ' ', strip_tags($category->getDescription()))) {
            $categoryData->setData($fieldName . '_t', $value);
        }

        $categoryData->setData('url_s_nonindex', $category->getUrl());
    }

    /**
     * @param Category $category
     * @param IndexDocument $categoryData
     */
    protected function _addBoostToCategoryData(Category $category, IndexDocument $categoryData)
    {
        if ($boost = $category->getSolrBoost()) {
            if ($boost > 0) {
                $categoryData->setData('_boost', $boost);
            } else {
                $categoryData->setData('_boost', 1);
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
     * @param boolean $emptyIndex
     * @param CategoryIterator $categoryCollection
     * @param int $storeId
     * @return int
     */
    protected function _indexCategoryCollection($emptyIndex, $categoryCollection, $storeId)
    {
        $combinedCategoryData = array();
        $idsForDeletion = array();

        foreach ($categoryCollection as $category) {
            if ($category->isIndexable($storeId)) {
                $combinedCategoryData[] = $this->_getCategoryData($category);
            } else {
                $idsForDeletion[] = $this->_getSolrId($category);
            }
        }
        
        if (!$emptyIndex && sizeof($idsForDeletion)) {
            $this->_getResource()->deleteByMultipleIds($storeId, $idsForDeletion);
        }

        if (sizeof($combinedCategoryData)) {
            $this->_getResource()->addDocuments($storeId, $combinedCategoryData);
            return $storeId;
        }
        return $storeId;
    }
}