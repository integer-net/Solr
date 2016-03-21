<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

class IntegerNet_Solr_Block_Result_Layer_Ajax extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $content = array(
            'products' => $this->getChildHtml('search.result'),
            'leftnav' => $this->getChildHtml('catalogsearch.solr.leftnav'),
            'topnav' => $this->getChildHtml('catalogsearch.solr.topnav'),
        );
        
        return Zend_Json::encode($content);
    }
}