<?php

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

/**
 * This class is only meant to be used as proxy for other helpers with concrete responsibilities.
 *
 * Use the methods to instantiate other helpers, this way it is ensured that the autoloader
 * is registered before.
 *
 */
class IntegerNet_SolrPro_Helper_Data extends IntegerNet_Solr_Helper_Data
{

    /**
     * @return IntegerNet_Solr_Helper_Autosuggest
     */
    public function autosuggest()
    {
        return Mage::helper('integernet_solrpro/autosuggest');
    }

    /**
     * @return IntegerNet_SolrPro_Helper_Factory
     */
    public function factory()
    {
        return Mage::helper('integernet_solrpro/factory');
    }
}