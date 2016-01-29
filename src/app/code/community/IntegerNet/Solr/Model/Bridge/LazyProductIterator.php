<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\ProductIterator;
use IntegerNet\Solr\Implementor\Product;

/**
 * Product iterator implementation with lazy loading of multiple collections (chunking).
 * Collections are prepared to be used by the indexer.
 */
class IntegerNet_Solr_Model_Bridge_LazyProductIterator implements ProductIterator, OuterIterator
{
    /**
     * @var int
     */
    protected $_storeId;
    /**
     * @var null|int[]
     */
    protected $_productIdFilter;
    /**
     * @var int
     */
    protected $_pageSize;
    /**
     * @var int
     */
    protected $_currentPage;
    /**
     * @var Mage_Catalog_Model_Resource_Product_Collection
     */
    protected $_collection;
    /**
     * @var ArrayIterator
     */
    protected $_collectionIterator;

    /**
     * @link http://php.net/manual/en/outeriterator.getinneriterator.php
     * @return Iterator The inner iterator for the current entry.
     */
    public function getInnerIterator()
    {
        return $this->_collectionIterator;
    }


    /**
     * @param int $_storeId store id for the collections
     * @param int[]|null $_productIdFilter array of product ids to be loaded, or null for all product ids
     * @param int $_pageSize Number of products per loaded collection (chunk)
     */
    public function __construct($_storeId, $_productIdFilter, $_pageSize)
    {
        $this->_storeId = $_storeId;
        $this->_productIdFilter = $_productIdFilter;
        $this->_pageSize = $_pageSize;
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->getInnerIterator()->next();
    }

    /**
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    /**
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        if ($this->getInnerIterator()->valid()) {
            return true;
        } elseif ($this->_currentPage < $this->_collection->getLastPageNumber()) {
            $this->_currentPage++;
            $this->_collection = self::getProductCollection($this->_storeId, $this->_productIdFilter, $this->_pageSize, $this->_currentPage);
            $this->_collectionIterator = $this->_collection->getIterator();
            $this->getInnerIterator()->rewind();
            return $this->getInnerIterator()->valid();
        }
        return false;
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->_currentPage = 1;
        $this->_collection = self::getProductCollection($this->_storeId, $this->_productIdFilter, $this->_pageSize, $this->_currentPage);
        $this->_collectionIterator = $this->_collection->getIterator();
        $this->_collectionIterator->rewind();
    }

    /**
     * @return Product
     */
    public function current()
    {
        $product = $this->getInnerIterator()->current();
        $product->setStoreId($this->_storeId);
        return new IntegerNet_Solr_Model_Bridge_Product($product);
    }

    /**
     * @param int $storeId
     * @param int[]|null $productIds
     * @param int $pageSize
     * @param int $pageNumber
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    private static function getProductCollection($storeId, $productIds = null, $pageSize = null, $pageNumber = 0)
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
            ->addAttributeToSelect(Mage::getSingleton('integernet_solr/bridge_attributeRepository')->getAttributeCodesToIndex());

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

        $productCollection->load();

        Mage::dispatchEvent('integernet_solr_product_collection_load_after', array(
            'collection' => $productCollection
        ));

        return $productCollection;
    }
}
