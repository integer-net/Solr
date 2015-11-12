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
 * Interface for configuration reader. One instance per store.
 */
interface IntegerNet_Solr_Config_Interface
{
    /**
     * Returns Solr server configuration
     *
     * @return IntegerNet_Solr_Config_Server
     */
    public function getServerConfig();

    /**
     * Returns indexing configuration
     *
     * @return IntegerNet_Solr_Config_Indexing
     */
    public function getIndexingConfig();
}