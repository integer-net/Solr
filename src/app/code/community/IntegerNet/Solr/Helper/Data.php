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
 * Use the methods of this class to instantiate other helpers, this way it is ensured that the autoloader
 * is registered before.
 */
class IntegerNet_Solr_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function __construct()
    {
        IntegerNet_Solr_Helper_Autoloader::createAndRegister();
    }

    /**
     * @return IntegerNet_Solr_Helper_Autosuggest
     */
    public function autosuggest()
    {
        return Mage::helper('integernet_solr/autosuggest');
    }

    /**
     * @return IntegerNet_Solr_Helper_Factory
     */
    public function factory()
    {
        return Mage::helper('integernet_solr/factory');
    }

    /**
     * @return IntegerNet_Solr_Helper_Attribute
     */
    public function attribute()
    {
        return Mage::helper('integernet_solr/attribute');
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active')) {
            return false;
        }

        if (!$this->isLicensed()) {
            return false;
        }
        
        if ($this->page()->isCategoryPage() && !$this->isCategoryDisplayActive()) {
            return false;
        }

        if (!$this->page()->isSolrResultPage()) {
            return false;
        }

        return true;
    }

    /**
     * @return IntegerNet_Solr_Helper_Page
     */
    public function page()
    {
        return Mage::helper('integernet_solr/page');
    }

    /**
     * @return bool
     * @deprecated use Page helper instead: page()->isSearchPage()
     */
    public function isSearchPage()
    {
        return $this->page()->isSearchPage();
    }

    /**
     * @return bool
     * @deprecated use Page helper instead: page()->isCategoryPage()
     */
    public function isCategoryPage()
    {
        return $this->page()->isCategoryPage();
    }

    /**
     * @return bool
     * @deprecated use Page helper instead: page()->isSolrResultPage()
     */
    public function isSolrResultPage()
    {
        return $this->page()->isSolrResultPage();
    }

    /**
     * @return bool
     */
    public function isCategoryDisplayActive()
    {
        return Mage::getStoreConfigFlag('integernet_solr/category/is_active');
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isKeyValid($key)
    {
        if (!$key) {
            return true;
        }
        $key = trim(strtolower($key));
        $key = str_replace(array('-', '_', ' '), '', $key);
        
        if (strlen($key) != 10) {
            return false;
        }
        
        $hash = md5($key);
        
        return substr($hash, -3) == 'f11';
    }

    /**
     * @return bool
     */
    public function isLicensed()
    {
        if (!$this->isKeyValid(Mage::getStoreConfig('integernet_solr/general/license_key'))) {

            if ($installTimestamp = Mage::getStoreConfig('integernet_solr/general/install_date')) {

                $diff = time() - $installTimestamp;
                if (($diff < 0) || ($diff > 2419200)) {

                    Mage::log('The IntegerNet_Solr module is not correctly licensed. Please enter your license key at System -> Configuration -> Solr or contact us via http://www.integer-net.com/solr-magento/.', Zend_Log::WARN, 'exception.log');
                    return false;

                } else if ($diff > 1209600) {

                    Mage::log('The IntegerNet_Solr module is not correctly licensed. Please enter your license key at System -> Configuration -> Solr or contact us via http://www.integer-net.com/solr-magento/.', Zend_Log::WARN, 'exception.log');
                }
            }
        }

        return true;
    }
}