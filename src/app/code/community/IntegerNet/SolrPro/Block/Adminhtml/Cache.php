<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Block_Adminhtml_Cache extends Mage_Adminhtml_Block_Template
{
    public function getFlushUrl()
    {
        return $this->getUrl('adminhtml/solr/flush');
    }
}