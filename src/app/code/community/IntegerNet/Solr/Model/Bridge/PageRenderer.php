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
    private $_itemBlocks = array();

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


}