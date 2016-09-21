<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

use IntegerNet\Solr\Exception;
use IntegerNet\SolrCms\Indexer\PageIndexer;

/**
 * Class IntegerNet_Solr_Model_CmsIndexer
 */
class IntegerNet_SolrPro_Model_CmsIndexer
{
    /**
     * @var PageIndexer
     */
    protected $_pageIndexer;

    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    public function __construct()
    {
        $this->_pageIndexer = Mage::helper('integernet_solr')->factory()->getPageIndexer();
    }

    /**
     * Rebuild all index data
     */
    public function reindexAll()
    {
        $this->_reindexPages(null, true);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function cmsPageSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Cms_Model_Page $page */
        $page = $observer->getObject();
        $this->_reindexPages(array($page->getId()));
    }

    /**
     * @param array|null $pageIds
     * @param boolean $emptyIndex
     */
    protected function _reindexPages($pageIds = null, $emptyIndex = false)
    {
        try {
            $this->_pageIndexer->reindex($pageIds, $emptyIndex);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function cmsPageDeleteAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Cms_Model_Page $page */
        $page = $observer->getObject();
        $this->_pageIndexer->deleteIndex(array($page->getId()));
    }

    /**
     * @param string[] $pageIds
     */
    protected function _deletePagesIndex($pageIds)
    {
        $this->_pageIndexer->deleteIndex($pageIds);
    }
}