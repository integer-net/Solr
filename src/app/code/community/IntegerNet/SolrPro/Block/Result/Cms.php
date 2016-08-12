<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Block_Result_Cms extends Mage_Core_Block_Template
{
    /**
     * @return IntegerNet_SolrPro_Model_Cms_Collection
     */
    public function getResultsCollection()
    {
        return Mage::getSingleton('integernet_solrpro/cms_collection');
    }
}