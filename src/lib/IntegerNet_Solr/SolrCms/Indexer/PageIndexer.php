<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\SolrCms\Indexer;
use IntegerNet\Solr\Implementor\StoreEmulation;
use IntegerNet\SolrCms\Implementor\PageRenderer;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrCms\Implementor\Page;
use IntegerNet\SolrCms\Implementor\PageIterator;
use IntegerNet\SolrCms\Implementor\PageRepository;
use IntegerNet\Solr\Indexer\IndexDocument;

class PageIndexer
{
    const CONTENT_TYPE = 'page';
    
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
    /** @var  PageRepository */
    private $_pageRepository;
    /** @var  PageRenderer */
    private $_renderer;
    /** @var StoreEmulation */
    private $storeEmulation;

    /**
     * @param int $defaultStoreId
     * @param Config[] $_config
     * @param ResourceFacade $_resource
     * @param EventDispatcher $_eventDispatcher
     * @param PageRepository $_pageRepository
     * @param PageRenderer $_renderer
     * @param StoreEmulation $storeEmulation
     */
    public function __construct($defaultStoreId, array $_config, ResourceFacade $_resource, EventDispatcher $_eventDispatcher,
                                PageRepository $_pageRepository, PageRenderer $_renderer, StoreEmulation $storeEmulation)
    {
        $this->_defaultStoreId = $defaultStoreId;
        $this->_config = $_config;
        $this->_resource = $_resource;
        $this->_eventDispatcher = $_eventDispatcher;
        $this->_pageRepository = $_pageRepository;
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
     * @param array|null $pageIds Restrict to given Pages if this is set
     * @param boolean|string $emptyIndex Whether to truncate the index before refilling it
     * @param null|int[]
     * @throws \Exception
     */
    public function reindex($pageIds = null, $emptyIndex = false, $restrictToStoreIds = null)
    {
        foreach($this->_config as $storeId => $storeConfig) {

            if (!$storeConfig->getGeneralConfig()->isActive()) {
                continue;
            }

            if (!$storeConfig->getCmsConfig()->isActive()) {
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

                $pageCollection = $this->_pageRepository->setPageSizeForIndex($pageSize)->getPagesForIndex($storeId, $pageIds);
                $this->_indexPageCollection($emptyIndex, $pageCollection, $storeId);

            } catch (\Exception $e) {
                $this->storeEmulation->stop();
                throw $e;
            }
            $this->storeEmulation->stop();
        }
    }

    /**
     * @param string[] $pageIds
     */
    public function deleteIndex($pageIds)
    {
        foreach($this->_config as $storeId => $storeConfig) {

            if (! $storeConfig->getGeneralConfig()->isActive()) {
                continue;
            }

            $ids = array();

            foreach($pageIds as $pageId) {
                $ids[] = 'page_' . $pageId . '_' . $storeId;
            }

            $this->_getResource()->deleteByMultipleIds($storeId, $ids);
        }
    }


    /**
     * Generate single page data for Solr
     *
     * @param Page $page
     * @return array
     */
    protected function _getPageData(Page $page)
    {
        $pageData = new IndexDocument(array(
            'id' => $page->getSolrId(), // primary identifier, must be unique
            'product_id' => $page->getId(),
            'store_id' => $page->getStoreId(),
            'content_type' => self::CONTENT_TYPE,
        ));

        $this->_addSearchDataToPageData($page, $pageData);

        $this->_addBoostToPageData($page, $pageData);

        $this->_eventDispatcher->dispatch('integernet_solr_get_page_data', array('page' => $page, 'page_data' => $pageData));

        return $pageData->getData();
    }

    /**
     * Get unique identifier for Solr
     *
     * @param \IntegerNet\SolrCms\Implementor\Page $page
     * @return string
     */
    protected function _getSolrId($page)
    {
        return 'page_' . $page->getId() . '_' . $page->getStoreId();
    }

    /**
     * @param Page $page
     * @param IndexDocument $pageData
     */
    protected function _addSearchDataToPageData(Page $page, IndexDocument $pageData)
    {
        $fieldName = 'title';

        $solrBoost = 1; /** @todo get correct value */
        if ($solrBoost != 1) {
            $pageData->setData($fieldName . '_boost', $solrBoost);
        }

        if ($value = $page->getTitle()) {
            $pageData->setData($fieldName . '_t', $value);
        }
        
        $fieldName = 'content';

        $solrBoost = 1; /** @todo get correct value */
        if ($solrBoost != 1) {
            $pageData->setData($fieldName . '_boost', $solrBoost);
        }

        if ($value = str_replace(array("\n", "\r"), ' ', strip_tags($page->getContent()))) {
            $pageData->setData($fieldName . '_t', $value);
        }

        $pageData->setData('url_s_nonindex', $page->getUrl());
    }

    /**
     * @param Page $page
     * @param IndexDocument $pageData
     */
    protected function _addResultHtmlToPageData(Page $page, IndexDocument $pageData)
    {
        $useHtmlForResults = $this->_getStoreConfig($page->getStoreId())->getResultsConfig()->isUseHtmlFromSolr();
        $this->_renderer->addResultHtmlToPageData($page, $pageData, $useHtmlForResults);
    }

    /**
     * @param Page $page
     * @param IndexDocument $pageData
     */
    protected function _addBoostToPageData(Page $page, IndexDocument $pageData)
    {
        if ($boost = $page->getSolrBoost()) {
            if ($boost > 0) {
                $pageData->setData('_boost', $boost);
            } else {
                $pageData->setData('_boost', 1);
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
     * @param PageIterator $pageCollection
     * @param int $storeId
     * @return int
     */
    protected function _indexPageCollection($emptyIndex, $pageCollection, $storeId)
    {
        $combinedPageData = array();
        $idsForDeletion = array();

        foreach ($pageCollection as $page) {
            if ($page->isIndexable($storeId)) {
                $combinedPageData[] = $this->_getPageData($page);
            } else {
                $idsForDeletion[] = $this->_getSolrId($page);
            }
        }
        
        if (!$emptyIndex && sizeof($idsForDeletion)) {
            $this->_getResource()->deleteByMultipleIds($storeId, $idsForDeletion);
        }

        if (sizeof($combinedPageData)) {
            $this->_getResource()->addDocuments($storeId, $combinedPageData);
            return $storeId;
        }
        return $storeId;
    }
}