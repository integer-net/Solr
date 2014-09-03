<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Helper_CatalogSearch_Data extends Mage_CatalogSearch_Helper_Data
{
    /**
     * Retrieve suggest url
     *
     * @return string
     */
    public function getSuggestUrl()
    {
        if (Mage::getStoreConfigFlag('integernet_solr/autosuggest/use_php_file_in_home_dir')) {
            return Mage::getBaseUrl() . 'autosuggest.php?store_id=' . Mage::app()->getStore()->getId();
        }

        return parent::getSuggestUrl();
    }
}