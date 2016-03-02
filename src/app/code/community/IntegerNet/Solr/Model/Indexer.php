<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
use IntegerNet\Solr\Exception;
use IntegerNet\Solr\Indexer\ProductIndexer;
use IntegerNet\SolrCms\Indexer\PageIndexer;

/**
 * Class IntegerNet_Solr_Model_Indexer
 * 
 * @todo fix URLs for comparison to not include referrer URL
 */
class IntegerNet_Solr_Model_Indexer extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * @var ProductIndexer
     */
    protected $_productIndexer;
    /**
     * @var PageIndexer
     */
    protected $_pageIndexer;
    /**
     * @var string[]
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
        ),
    );

    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $autoloader = new IntegerNet_Solr_Helper_Autoloader();
        $autoloader->createAndRegister();

        $this->_productIndexer = Mage::helper('integernet_solr/factory')->getProductIndexer();
        $this->_pageIndexer = Mage::helper('integernet_solr/factory')->getPageIndexer();
    }


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
        $this->_reindexCmsPages(null, true);
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
        try {
            $this->_productIndexer->reindex($productIds, $emptyIndex);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @param array|null $pageIds
     * @param boolean $emptyIndex
     */
    protected function _reindexCmsPages($pageIds = null, $emptyIndex = false)
    {
        try {
            $this->_pageIndexer->reindex($pageIds, $emptyIndex);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @param string[] $productIds
     */
    protected function _deleteProductsIndex($productIds)
    {
        $this->_productIndexer->deleteIndex($productIds);
    }
}