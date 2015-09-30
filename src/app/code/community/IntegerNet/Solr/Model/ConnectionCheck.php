<?php

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_ConnectionCheck
{
    protected $_flag = null;

    public function checkConnection()
    {
        $errors = array();
        foreach (Mage::app()->getStores() as $store) {
            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $store->getId())) {
                continue;
            }
            if (!Mage::getStoreConfigFlag('integernet_solr/server/check_connection', $store->getId())) {
                continue;
            }
            $checkMessages = Mage::getModel('integernet_solr/configuration')->getMessages($store->getId());
            if (isset($checkMessages['error']) && sizeof($checkMessages['error'])) {
                $errors[$store->getId()] = $checkMessages['error'];
            }
        }

        if (!$this->_getErrorFlag()->getFlagData()) {
            if (sizeof($errors)) {
                /** @var $flag Mage_Core_Model_Flag */
                $flag = $this->_getErrorFlag();
                $errorCount = intval($flag->getFlagData()) + 1;
                $flag->setFlagData($errorCount)->save();
                $this->_sendErrorEmail($errors);
            }
        } else {
            if (!sizeof($errors)) {
                Mage::log('Back to normal', null, 'solr-errors.log');
                /** @var $flag Mage_Core_Model_Flag */
                $flag = $this->_getErrorFlag();
                $flag->setFlagData(0)->save();
            } else {
                Mage::log('Errors already found, skipping', null, 'solr-errors.log');
            }
        }
    }

    /**
     * @return Mage_Core_Model_Flag
     */
    protected function _getErrorFlag()
    {
        if (is_null($this->_flag)) {
            $this->_flag = Mage::getModel('core/flag', array('flag_code' => 'solr_connection_error_count'))->loadSelf();
        }
        return $this->_flag;
    }

    /**
     * @param $errors
     */
    protected function _sendErrorEmail($errors)
    {
        $templateId = Mage::getStoreConfig('integernet_solr/connection_check/email_template');
        $sender = Mage::getStoreConfig('integernet_solr/connection_check/identity');
        $recipients = Mage::getStoreConfig('integernet_solr/connection_check/recipient_emails');

        foreach(explode(',', $recipients) as $recipient) {

            Mage::getModel('core/email_template')
                ->sendTransactional(
                    $templateId,
                    $sender,
                    trim($recipient),
                    '',
                    array(
                        'notification_text' => print_r($errors, true),
                    )
                );
        }
        Mage::log($errors, null, 'solr-errors.log');
    }
}