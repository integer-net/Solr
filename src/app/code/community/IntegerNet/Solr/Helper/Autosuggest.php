<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Helper_Autosuggest extends Mage_Core_Helper_Abstract
{
    /**
     * Store Solr configuration in serialized text field so it can be accessed from autosuggest later
     */
    public function storeSolrConfig()
    {
        $config = array();
        foreach(Mage::app()->getStores(false) as $store) { /** @var Mage_Core_Model_Store $store */
            $config[$store->getId()] = Mage::getStoreConfig('integernet_solr', $store);
        }

        $filename = Mage::getBaseDir('var') . DS . 'integernet_solr' . DS . 'config.txt';
        file_put_contents($filename, serialize($config));
    }
}