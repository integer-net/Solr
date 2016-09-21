<?php

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Model_ConnectionCheck
{
    protected $_flag = null;

    public function checkConnection()
    {
        $errors = array();
        $hasCheckedAStore = false;
        foreach (Mage::app()->getStores() as $store) { /** @var Mage_Core_Model_Store $store */
            if (!$store->getIsActive()) {
                continue;
            }
            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $store->getId())) {
                continue;
            }
            if (!Mage::getStoreConfigFlag('integernet_solr/connection_check/is_active', $store->getId())) {
                continue;
            }
            $hasCheckedAStore = true;
            $checkMessages = Mage::getModel('integernet_solr/configuration')->getMessages($store->getId());
            if (isset($checkMessages['error']) && sizeof($checkMessages['error'])) {
                $errors[$store->getId()] = $checkMessages['error'];
            }
        }

        if (!$hasCheckedAStore) {
            return;
        }
        
        $minErrorCount = intval(Mage::getStoreConfig('integernet_solr/connection_check/send_email_on_nth_failure'));
        $flagExists = !is_null($this->_getErrorFlag()->getFlagData());
        $currentErrorCount = intval($this->_getErrorFlag()->getFlagData());
        if (sizeof($errors)) {
            if (!$flagExists) {
                return; // don't do anything on errors if check wasn't successful once
            }
            $currentErrorCount++;
            if ($currentErrorCount == $minErrorCount) {
                $this->_sendErrorEmail($errors);
            }
            $this->_getErrorFlag()->setFlagData($currentErrorCount)->save();
        } else {
            if ($currentErrorCount >= $minErrorCount) {
                $this->_sendRestoredEmail();
            }
            if ($currentErrorCount > 0 || !$flagExists) {
                $this->_getErrorFlag()->setFlagData(0)->save();
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
     * @param array $errors
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
                        'notification_text' => $this->_getErrorNotificationText($errors),
                        'base_url' => Mage::getStoreConfig('web/unsecure/base_url'),
                    )
                );
        }
    }

    /**
     * @param array $errors
     * @return string
     */
    protected function _getErrorNotificationText($errors)
    {
        $text = '';
        foreach ($errors as $storeId => $storeErrorMessages) {
            $headline = Mage::helper('integernet_solr')->__('Errors for Store "%s":', Mage::app()->getStore($storeId)->getName());
            $text .= $headline . PHP_EOL;
            $text .= str_repeat('=', strlen($headline)) . PHP_EOL;
            foreach($storeErrorMessages as $message) {
                $text .= '- ' . $message . PHP_EOL;
            }
            $text .= PHP_EOL;
        }

        return $text;
    }

    /**
     */
    protected function _sendRestoredEmail()
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
                        'notification_text' => $this->_getRestoredNotificationText(),
                        'base_url' => Mage::getStoreConfig('web/unsecure/base_url'),
                    )
                );
        }
    }

    /**
     * @return string
     */
    protected function _getRestoredNotificationText()
    {
        return Mage::helper('integernet_solr')->__('Connection to Solr Server has been restored.');
    }
}