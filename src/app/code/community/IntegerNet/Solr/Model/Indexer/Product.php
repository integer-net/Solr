<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
use IntegerNet\Solr\Indexer\ProductIndexer;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Attribute;

/**
 * @todo extract factory method for ProductIndexer, eliminate this class
 */
class IntegerNet_Solr_Model_Indexer_Product
{
    /**
     * @var ProductIndexer
     */
    protected $_indexer;

    public function __construct()
    {
        $defaultStoreId = Mage::app()->getStore(true)->getId();
        $this->_indexer = new ProductIndexer(
            $defaultStoreId,
            Mage::helper('integernet_solr/factory')->getStoreConfig(),
            Mage::helper('integernet_solr/factory')->getSolrResource(),
            Mage::helper('integernet_solr'),
            Mage::getSingleton('integernet_solr/bridge_attributeRepository'),
            Mage::getModel('integernet_solr/bridge_categoryRepository'),
            Mage::getModel('integernet_solr/bridge_productRepository'),
            Mage::getModel('integernet_solr/indexer_product_renderer')
        );
    }

    /**
     * @param array|null $productIds Restrict to given Products if this is set
     * @param boolean|string $emptyIndex Whether to truncate the index before refilling it
     * @param null|Mage_Core_Model_Store $restrictToStore
     */
    public function reindex($productIds = null, $emptyIndex = false, $restrictToStore = null)
    {
        return $this->_indexer->reindex($productIds, $emptyIndex, $restrictToStore);
    }

    /**
     * @param string[] $productIds
     */
    public function deleteIndex($productIds)
    {
        return $this->_indexer->deleteIndex($productIds);
    }
}