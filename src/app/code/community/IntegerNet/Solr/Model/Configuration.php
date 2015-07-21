<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Configuration
{
    protected $_messages = array();

    public function getMessages($storeId = null)
    {
        $this->_checkConfiguration($storeId);
        return $this->_messages;
    }

    protected function _checkConfiguration($storeId = null)
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
            $this->_addNoticeMessage(
                Mage::helper('integernet_solr')->__('Solr Module is not activated.')
            );
            return;
        } else {
            $this->_addSuccessMessage(
                Mage::helper('integernet_solr')->__('Solr Module is activated.')
            );
        }

        if (!Mage::getStoreConfig('integernet_solr/server/host', $storeId)
        || !Mage::getStoreConfig('integernet_solr/server/port', $storeId)
        || !Mage::getStoreConfig('integernet_solr/server/path', $storeId)) {
            $this->_addErrorMessage(
                Mage::helper('integernet_solr')->__('Solr server configuration is incomplete.')
            );
            return;
        } else {
            $this->_addSuccessMessage(
                Mage::helper('integernet_solr')->__('Solr server configuration is complete.')
            );
        }

        $solr = Mage::getResourceModel('integernet_solr/solr')->getSolr($storeId);

        if (!$solr->ping()) {
            $this->_addErrorMessage(
                Mage::helper('integernet_solr')->__('Connection to Solr server failed.')
            );
            return;
        } else {
            $this->_addSuccessMessage(
                Mage::helper('integernet_solr')->__('Connection to Solr server estalished successfully.')
            );
        }
    }

    /**
     * @param string $text
     * @param string $type
     */
    protected function _addMessage($text, $type)
    {
        $this->_messages[$type][] = $text;
    }

    /**
     * @param string $text
     */
    protected function _addErrorMessage($text)
    {
        $this->_addMessage($text, 'error');
    }

    /**
     * @param string $text
     */
    protected function _addSuccessMessage($text)
    {
        $this->_addMessage($text, 'success');
    }

    /**
     * @param string $text
     */
    protected function _addWarningMessage($text)
    {
        $this->_addMessage($text, 'warning');
    }

    /**
     * @param string $text
     */
    protected function _addNoticeMessage($text)
    {
        $this->_addMessage($text, 'notice');
    }


}