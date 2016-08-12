<?php
/**
 * integer_net Magento Module
 *
 * DO NOT CHANGE THIS FILE! Copy it to autosuggest.config.php if you want to change the configuration
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\SolrSuggest\Plain\AppConfig;

return AppConfig::defaultConfig()
    /*
     * Callback that returns the application specific SolrSuggest Factory implementation
     *
     * Used to initialize cache on the fly
     */
    ->withLoadApplicationCallback(function()
    {
        require_once 'app/Mage.php';
        Mage::app();
        return Mage::helper('integernet_solr/factory');
    })
    /*
     * Base directory for our libraries. Adjust it here if this is different, it cannot be loaded from the Application
     * configuration without initialized autoloader.
     *
     * This is only used if you don't specify a path to an external autoloader (i.e. composer)
     *
     * use real directory of current file in case of symlinks
     */
    ->withLibBaseDir(__DIR__ . '/lib/IntegerNet_Solr')
    /*
     * Base directory for cache.
     *
     * This is only used if you use the default file based cache.
     *
     * use directory relative to cwd (Magento root)
     */
    ->withCacheBaseDir('var/cache/integernet_solr')
;

