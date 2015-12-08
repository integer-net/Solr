<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Model_Indexer_Product_Repository
{
    /**
     * @param int $storeId
     * @param int[]|null $productIds
     * @param int $pageSize
     * @param int $pageNumber
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getProductCollection($storeId, $productIds = null, $pageSize = null, $pageNumber = 0)
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
            ->addAttributeToSelect(Mage::helper('integernet_solr')->getAttributeCodesToIndex());

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

        //TODO load

        Mage::dispatchEvent('integernet_solr_product_collection_load_after', array(
            'collection' => $productCollection
        ));

        return $productCollection;
    }
}