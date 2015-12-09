<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

use IntegerNet\Solr\Implementor\ProductRenderer;
use IntegerNet\Solr\Implementor\Product;
use IntegerNet\Solr\Indexer\IndexDocument;

class IntegerNet_Solr_Model_Indexer_Product_Renderer implements ProductRenderer
{
    /** @var IntegerNet_Solr_Block_Indexer_Item[] */
    protected $_itemBlocks = array();
    protected $_currentStoreId = null;
    protected $_isEmulated = false;
    protected $_initialEnvironmentInfo = null;

    /**
     * @param Product $product
     * @param IndexDocument $productData
     * @param bool $useHtmlInResults
     */
    public function addResultHtmlToProductData(Product $product, IndexDocument $productData, $useHtmlInResults)
    {
        if (! $product instanceof IntegerNet_Solr_Model_Bridge_Product) {
            // We need direct access to the Magento product
            throw new InvalidArgumentException('Magento 1 product bridge expected, '. get_class($product) .' received.');
        }
        $product = $product->getMagentoProduct();
        $storeId = $product->getStoreId();
        if ($this->_currentStoreId != $storeId) {

            $this->_emulateStore($storeId);
        }

        /** @var IntegerNet_Solr_Block_Indexer_Item $block */
        $block = $this->_getResultItemBlock();

        $block->setProduct($product);

        $block->setTemplate('integernet/solr/result/autosuggest/item.phtml');
        $productData->setData('result_html_autosuggest_nonindex', $block->toHtml());

        if ($useHtmlInResults) {
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

    public function stopStoreEmulation()
    {
        if ($this->_isEmulated && $this->_initialEnvironmentInfo) {
            Mage::getSingleton('core/app_emulation')->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
        }
    }
}