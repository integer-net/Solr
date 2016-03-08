<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

use IntegerNet\Solr\Implementor\StoreEmulation;

class IntegerNet_Solr_Model_Bridge_StoreEmulation implements StoreEmulation
{
    protected $_currentStoreId = null;
    protected $_isEmulated = false;
    protected $_initialEnvironmentInfo = null;
    protected $_unsecureBaseConfig = array();

    /**
     * @param int $storeId
     * @throws Mage_Core_Exception
     */
    public function start($storeId)
    {
        $this->stop();
        $newLocaleCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId);
        $this->_currentStoreId = $storeId;
        $this->_initialEnvironmentInfo = Mage::getSingleton('core/app_emulation')->startEnvironmentEmulation($storeId);
        $this->_isEmulated = true;
        Mage::app()->getLocale()->setLocaleCode($newLocaleCode);
        Mage::getSingleton('core/translate')->setLocale($newLocaleCode)->init(Mage_Core_Model_App_Area::AREA_FRONTEND, true);
        Mage::getDesign()->setStore($storeId);
        Mage::getDesign()->setPackageName();
        $themeName = Mage::getStoreConfig('design/theme/default', $storeId);
        Mage::getDesign()->setTheme($themeName);

        $this->_unsecureBaseConfig[$storeId] = Mage::getStoreConfig('web/unsecure', $storeId);
        $store = Mage::app()->getStore($storeId);
        $store->setConfig('web/unsecure/base_skin_url', Mage::getStoreConfig('web/secure/base_skin_url', $storeId));
        $store->setConfig('web/unsecure/base_media_url', Mage::getStoreConfig('web/secure/base_media_url', $storeId));
        $store->setConfig('web/unsecure/base_js_url', Mage::getStoreConfig('web/secure/base_js_url', $storeId));
    }

    public function stop()
    {
        if (isset($this->_unsecureBaseConfig[$this->_currentStoreId])) {
            $store = Mage::app()->getStore($this->_currentStoreId);
            $store->setConfig('web/unsecure/base_skin_url', $this->_unsecureBaseConfig[$this->_currentStoreId]['base_skin_url']);
            $store->setConfig('web/unsecure/base_media_url', $this->_unsecureBaseConfig[$this->_currentStoreId]['base_media_url']);
            $store->setConfig('web/unsecure/base_js_url', $this->_unsecureBaseConfig[$this->_currentStoreId]['base_js_url']);
        }

        if ($this->_isEmulated && $this->_initialEnvironmentInfo) {
            Mage::getSingleton('core/app_emulation')->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
        }
    }

    public function __destruct()
    {
        $this->stop();
    }
}