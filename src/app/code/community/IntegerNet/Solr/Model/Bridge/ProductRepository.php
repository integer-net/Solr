<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\PagedProductIterator;
use IntegerNet\Solr\Implementor\Product;
use IntegerNet\Solr\Implementor\ProductRepository;
use IntegerNet\Solr\Implementor\ProductIterator;

class IntegerNet_Solr_Model_Bridge_ProductRepository implements ProductRepository
{
    protected $_bridgeFactory;

    public function __construct()
    {
        $this->_bridgeFactory = Mage::getModel('integernet_solr/bridge_factory');
    }

    /**
     * @var int
     */
    protected $_pageSize;

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSizeForIndex($pageSize)
    {
        $this->_pageSize = $pageSize;
        return $this;
    }

    /**
     * Return product iterator, which may implement lazy loading
     *
     * @param int $storeId Products will be returned that are visible in this store and with store specific values
     * @param null|int[] $productIds filter by product ids
     * @return PagedProductIterator
     */
    public function getProductsForIndex($storeId, $productIds = null)
    {
        // hier child ids für alle product ids laden, zusammen mit zurdnung parent => child, Zuordnung wird als ass. array
        // im repository ($this->associatíons) gespeichert, der iterator wird ebenfalls hier gespeichert ($this->currentIterator)
        // id arrays je nach pagesize zerlegen und ($productIdsChunks, $childIdsChunks) statt ($productIds, $pageSize) übergeben
        // 
        // class ProductIdChunk { array $parentIds, array $childIds }
        
        // im lazy product iterator: beim laden des jeweiligen chunks werden auch alle children geladen (2. collection)
        return $this->_bridgeFactory->createLazyProductIterator($storeId, $productIds, $this->_pageSize);
    }

    /**
     * Return product iterator for child products
     *
     * @param Product|IntegerNet_Solr_Model_Bridge_Product $parent The composite parent product. Child products will be returned that are visible in the same store and with store specific values
     * @return ProductIterator
     */
    public function getChildProducts(Product $parent)
    {
        // Zugriff auf $this->associations, keine weiteren DB Abfragen
        // exception werfen wenn produkt nicht in den associations vorhanden
        $magentoProduct = $parent->getMagentoProduct();
        $childProductIds = $magentoProduct->getTypeInstance(true)->getChildrenIds($magentoProduct->getId());

        if (sizeof($childProductIds) && is_array(current($childProductIds))) {
            $childProductIds = current($childProductIds);
        }

        if (!sizeof($childProductIds)) {
            Mage::throwException('Product ' . $magentoProduct->getSku() . ' doesn\'t have any child products.');
        }

        /** @var $childProductCollection Mage_Catalog_Model_Resource_Product_Collection */
        $childProductCollection = Mage::getResourceModel('catalog/product_collection');
        $childProductCollection
            ->setStoreId($magentoProduct->getStoreId())
            ->addAttributeToFilter('entity_id', array('in' => $childProductIds))
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
            ->addAttributeToSelect(Mage::getModel('integernet_solr/bridge_factory')->getAttributeRepository()->getAttributeCodesToIndex());

        return $this->_bridgeFactory->createProductIterator($childProductCollection);

    }


}