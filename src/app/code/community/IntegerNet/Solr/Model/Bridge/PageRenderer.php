<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

use IntegerNet\SolrCms\Implementor\PageRenderer;
use IntegerNet\SolrCms\Implementor\Page;
use IntegerNet\Solr\Indexer\IndexDocument;

class IntegerNet_Solr_Model_Bridge_PageRenderer implements PageRenderer
{
    /** @var IntegerNet_Solr_Block_Indexer_Item[] */
    protected $_itemBlocks = array();
    protected $_currentStoreId = null;
    protected $_isEmulated = false;
    protected $_initialEnvironmentInfo = null;
    protected $_unsecureBaseConfig = array();

    /**
     * @param Page $page
     * @param IndexDocument $pageData
     * @param bool $useHtmlInResults
     */
    public function addResultHtmlToPageData(Page $page, IndexDocument $pageData, $useHtmlInResults)
    {
        if (! $page instanceof IntegerNet_Solr_Model_Bridge_Page) {
            // We need direct access to the Magento page
            throw new InvalidArgumentException('Magento 1 page bridge expected, '. get_class($page) .' received.');
        }
        $page = $page->getMagentoPage();
        $storeId = $page->getStoreId();
        if ($this->_currentStoreId != $storeId) {

            $this->_emulateStore($storeId);
        }

        /** @var IntegerNet_Solr_Block_Indexer_Item $block */
        $block = $this->_getResultItemBlock();

        $block->setPage($page);

        $block->setTemplate('integernet/solr/result/autosuggest/item.phtml');
        $pageData->setData('result_html_autosuggest_nonindex', $block->toHtml());

        if ($useHtmlInResults) {
            $block->setTemplate('integernet/solr/result/list/item.phtml');
            $pageData->setData('result_html_list_nonindex', $block->toHtml());

            $block->setTemplate('integernet/solr/result/grid/item.phtml');
            $pageData->setData('result_html_grid_nonindex', $block->toHtml());
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
            $this->_itemBlocks[Mage::app()->getStore()->getId()] = $block;
        }

        return $this->_itemBlocks[Mage::app()->getStore()->getId()];
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

        $this->_unsecureBaseConfig[$storeId] = Mage::getStoreConfig('web/unsecure', $storeId);
        $store = Mage::app()->getStore($storeId);
        $store->setConfig('web/unsecure/base_skin_url', Mage::getStoreConfig('web/secure/base_skin_url', $storeId));
        $store->setConfig('web/unsecure/base_media_url', Mage::getStoreConfig('web/secure/base_media_url', $storeId));
        $store->setConfig('web/unsecure/base_js_url', Mage::getStoreConfig('web/secure/base_js_url', $storeId));
    }

    public function stopStoreEmulation()
    {
        if (isset($this->_unsecureBaseConfig[$this->_currentStoreId])) {
            $store = Mage::app()->getStore($this->_currentStoreId);
            $store->setConfig('web/unsecure/base_skin_url', $this->_unsecureBaseConfig[$this->_currentStoreId]['base_skin_url']);
            $store->setConfig('web/unsecure/base_media_url', $this->_unsecureBaseConfig[$this->_currentStoreId]['base_media_url']);
            $store->setConfig('web/unsecure/base_js_url', $this->_unsecureBaseConfig[$this->_currentStoreId]['base_js_url']);
        }

        if ($this->_isEmulated && $this->_initialEnvironmentInfo) {
            Mage::getSingleton('core/app_emulation')->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
        }
    }
}