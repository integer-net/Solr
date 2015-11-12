<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Helper_Factory
{
    /**
     * Returns new configured Solr recource
     *
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    public function getSolrResource()
    {
        $storeConfig = [];
        foreach (Mage::app()->getStores() as $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($store->getIsActive()) {
                $storeConfig[$store->getId()] = new IntegerNet_Solr_Model_Config_Store($store->getId());
            }
        }
        return new IntegerNet_Solr_Model_Resource_Solr($storeConfig);
    }
}