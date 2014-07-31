<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Indexer extends Mage_Index_Model_Indexer_Abstract
{
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
        ),
    );

    protected $_resourceName = 'integernet_solr/indexer';

    public function getName()
    {
        return Mage::helper('integernet_solr')->__('Solr Search Index');
    }

    public function getDescription()
    {
        return Mage::helper('integernet_solr')->__('Indexing of Product Data for Solr');
    }

    /**
     * Rebuild all index data
     */
    public function reindexAll()
    {
        $this->_reindexProducts(null, true);
    }

    /**
     * @param Mage_Index_Model_Event $event
     * @return $this
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        if ($event->getEntity() == Mage_Catalog_Model_Product::ENTITY) {

            $productIds = array();

            /* @var $object Varien_Object */
            $object = $event->getDataObject();

            switch ($event->getType()) {
                case Mage_Index_Model_Event::TYPE_SAVE:
                    $productIds[] = $object->getId();
                    break;

                case Mage_Index_Model_Event::TYPE_DELETE:
                    $event->addNewData('solr_delete_product_skus', array($object->getId()));
                    break;

                case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                    $productIds = $object->getProductIds();
                    break;
            }

            if (sizeof($productIds)) {
                $event->addNewData('solr_update_product_ids', $productIds);
            }

        }
        return $this;
    }

    /**
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();

        if (isset($data['solr_delete_product_skus'])) {
            $productSkus = $data['solr_delete_product_skus'];
            if (is_array($productSkus) && !empty($productSkus)) {

                $this->_deleteProductsIndex($productSkus);
            }
        }

        if (isset($data['solr_update_product_ids'])) {
            $productIds = $data['solr_update_product_ids'];
            if (is_array($productIds) && !empty($productIds)) {

                $this->_reindexProducts($productIds);
            }
        }
    }

    /**
     * @param array|null $productIds
     * @param boolean $emptyIndex
     */
    protected function _reindexProducts($productIds = null, $emptyIndex = false)
    {
        Mage::getSingleton('integernet_solr/indexer_product')->reindex($productIds, $emptyIndex);
    }

    /**
     * @param string[] $productIds
     */
    protected function _deleteProductsIndex($productIds)
    {
        Mage::getSingleton('integernet_solr/indexer_product')->deleteIndex($productIds);
    }
}