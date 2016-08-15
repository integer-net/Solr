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
    
    /** @var PagedProductIterator */
    protected $_currentIterator;
    
    /** @var  array */
    protected $_associations;

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
        Mage::app()->getStore($storeId)->setConfig('catalog/frontend/flat_catalog_product', 0);
        
        $associations = $this->_getAssociations($productIds);
        $allProductIds = $this->_getAllProductIds($productIds);

        /** @var IntegerNet_Solr_Model_Bridge_ProductIdChunk[] $productIdChunks */
        $productIdChunks = $this->_getProductIdChunks($allProductIds, $associations);

        $this->_currentIterator = $this->_bridgeFactory->createLazyProductIterator($storeId, $productIdChunks);
        return $this->_currentIterator;
    }

    /**
     * Return product iterator for child products
     *
     * @param Product|IntegerNet_Solr_Model_Bridge_Product $parent The composite parent product. Child products will be returned that are visible in the same store and with store specific values
     * @return Varien_Data_Collection
     */
    public function getChildProducts(Product $parent)
    {
        $magentoProduct = $parent->getMagentoProduct();

        if (!isset($this->_associations[$magentoProduct->getId()])) {
            // Exception will be caught; this happens regularily if no children are present
            Mage::throwException('Children Products for product ' . $magentoProduct->getId() . ' haven\'t been preloaded.');
        }
        $childProductIds = $this->_associations[$magentoProduct->getId()];
        
        $childProductCollection = new Varien_Data_Collection();
        foreach($childProductIds as $childProductId) {
            /** @var Mage_Catalog_Model_Resource_Product_Collection $productCollection */
            $productCollection = $this->_currentIterator->getDataSource();
            /** @var Mage_Catalog_Model_Product $childProduct */
            $childProduct = $productCollection->getItemById($childProductId);
            if (is_null($childProduct)) {
                Mage::throwException('Child Product ' . $childProductId . ' for product ' . $magentoProduct->getId() . ' isn\'t included in the collection.');
            }
            if ($childProduct->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
                $childProductCollection->addItem($childProduct);
            }
        }
        
        return $this->_bridgeFactory->createProductIterator($childProductCollection);
    }

    /**
     * @param null|int[] $productIds
     * @return int[][] An array with parent_id as key and children ids as value 
     */
    protected function _getAssociations($productIds)
    {
        if (is_null($this->_associations)) {
            
            /** @var $configurableResourceTypeModel IntegerNet_Solr_Model_Resource_Catalog_Product_Type_Configurable */
            $configurableResourceTypeModel = Mage::getResourceModel('integernet_solr/catalog_product_type_configurable');
            $this->_associations = $configurableResourceTypeModel->getChildrenIdsForMultipleParents($productIds);

            /** @var $groupedResourceTypeModel IntegerNet_Solr_Model_Resource_Catalog_Product_Type_Grouped */
            $groupedResourceTypeModel = Mage::getResourceModel('integernet_solr/catalog_product_type_grouped');
            // Don't use array_merge here due to performance reasons
            foreach ($groupedResourceTypeModel->getChildrenIdsForMultipleParents($productIds) as $parentId => $childrenIds) {
                $this->_associations[$parentId] = $childrenIds;
            }
        }
        return $this->_associations;
    }

    /**
     * @param int[] $productIds
     * @return int[]
     */
    protected function _getAllProductIds($productIds)
    {
        /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection');
        if (is_array($productIds)) {
            $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
        }
        $productIds = $productCollection->getAllIds();
        return $productIds;
    }

    /**
     * @param int[] $allProductIds
     * @param int[][] $associations
     * @return IntegerNet_Solr_Model_Bridge_ProductIdChunk[]
     */
    protected function _getProductIdChunks($allProductIds, $associations)
    {
        $productIdChunks = array();
        $currentParentIds = array();
        $currentChildrenIds = array();
        $currentChunkSize = 0;
        foreach ($allProductIds as $key => $productId) {
            $productCount = 1;
            if (isset($associations[$productId])) {
                $productCount += sizeof($associations[$productId]);
            }
            if ($currentChunkSize > 0 && $currentChunkSize + $productCount > $this->_pageSize) {
                $productIdChunks[] = new IntegerNet_Solr_Model_Bridge_ProductIdChunk($currentParentIds, $currentChildrenIds);
                $currentParentIds = array();
                $currentChildrenIds = array();
                $currentChunkSize = 0;
            }
            $currentParentIds[] = $productId;
            if (isset($associations[$productId])) {
                $currentChildrenIds[$productId] = $associations[$productId];
            }
            $currentChunkSize += $productCount;
        }
        $productIdChunks[] = new IntegerNet_Solr_Model_Bridge_ProductIdChunk($currentParentIds, $currentChildrenIds);
        return $productIdChunks;
    }
}