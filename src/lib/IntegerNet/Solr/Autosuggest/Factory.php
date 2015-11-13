<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * This class is a low weight replacement for the factory helper class in autosuggest calls
 */
final class IntegerNet_Solr_Autosuggest_Factory implements IntegerNet_Solr_Interface_Factory
{
    /**
     * Returns new configured Solr recource
     *
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    public function getSolrResource()
    {
        $store = IntegerNet_Solr_Autosuggest_Mage::app()->getStore();
        $storeConfig = [
            $store->getId() => new IntegerNet_Solr_Model_Config_Store($store->getId())
        ];

        return new IntegerNet_Solr_Model_Resource_Solr($storeConfig);
    }
}