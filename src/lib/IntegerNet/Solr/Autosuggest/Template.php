<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Autosuggest_Template implements \IntegerNet\SolrSuggest\Implementor\Template
{
    public function getFilename()
    {
        return Mage::getStoreConfig('template_filename');
    }

}