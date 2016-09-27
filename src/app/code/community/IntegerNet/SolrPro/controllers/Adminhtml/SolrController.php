<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Adminhtml_SolrController extends Mage_Adminhtml_Controller_Action
{
    public function flushAction()
    {
        Mage::getSingleton('adminhtml/session')->addSuccess(
            $this->__('The Solr Autosuggest Cache has been flushed and rebuilt successfully.')
        );
        Mage::helper('integernet_solrpro')->autosuggest()->storeSolrConfig();
        $this->_redirectReferer();
    }

    protected function _isAllowed()
    {
        return true;
    }
}