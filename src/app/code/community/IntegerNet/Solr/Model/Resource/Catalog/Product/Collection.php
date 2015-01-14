<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Resource_Catalog_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    /** @var IntegerNet_Solr_Model_Result_Collection */
    protected $_solrResultCollection;

    /**
     * @param IntegerNet_Solr_Model_Result_Collection $solrResultCollection
     * @return IntegerNet_Solr_Model_Resource_Catalog_Product_Collection
     */
    public function setSolrResultCollection($solrResultCollection)
    {
        $productIds = $solrResultCollection->getColumnValues('product_id');
        $this->addAttributeToFilter('entity_id', array('in' => $productIds));
        $this->_solrResultCollection = $solrResultCollection;
        return $this;
    }

    /**
     * @return IntegerNet_Solr_Model_Result_Collection
     */
    public function getSolrResultCollection()
    {
        if (is_null($this->_solrResultCollection)) {
            $this->setSolrResultCollection(Mage::getSingleton('integernet_solr/result_collection'));
        }
        return $this->_solrResultCollection;
    }
    
    protected function _beforeLoad()
    {
        if (Mage::getStoreConfigFlag('integernet_solr/general/is_active') && is_null($this->_solrResultCollection)) {
            $this->setSolrResultCollection(Mage::getSingleton('integernet_solr/result_collection'));
        }

        return parent::_beforeLoad();
    }

    /**
     * Bring collection items into order from solr
     * 
     * @return IntegerNet_Solr_Model_Resource_Catalog_Product_Collection
     */
    protected function _afterLoad()
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active')) {
            return parent::_afterLoad();
        }

        parent::_afterLoad();

        $tempItems = array();
        foreach($this->getSolrResultCollection()->getColumnValues('product_id') as $itemId) {
            $item = $this->getItemById($itemId);
            if (!is_null($item)) {
                $tempItems[$itemId] = $item;
            }
        }
        $this->_items = $tempItems;
        
        return $this;
    }

    /**
     * Get Collection size from Solr
     * 
     * @return int
     */
    public function getSize()
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active')) {
            return parent::getSize();
        }
        return $this->getSolrResultCollection()->getSize();
    }
}