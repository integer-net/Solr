<?php
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\SolrSuggest\Implementor\SearchUrl;
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableAttributes(Mage::app()->getStore()->getId(), $useAlphabeticalSearch);
    }
    
    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableInSearchAttributes(Mage::app()->getStore()->getId(), $useAlphabeticalSearch);
    }


    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableInCatalogAttributes(Mage::app()->getStore()->getId(), $useAlphabeticalSearch);

    }

    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableInCatalogOrSearchAttributes(Mage::app()->getStore()->getId(), $useAlphabeticalSearch);
    }

    /**
     * @deprecated use repository directly
     * @return string[]
     */
    public function getAttributeCodesToIndex()
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')->getAttributeCodesToIndex();
    }


    /**
     * @deprecated use IndexField directly
     * @param Attribute $attribute
     * @param bool $forSorting
     * @return string
     */
    public function getFieldName($attribute, $forSorting = false)
    {
        if (! $attribute instanceof Attribute) {
            $attribute = new IntegerNet_Solr_Model_Bridge_Attribute($attribute);
        }
        $indexField = new \IntegerNet\Solr\Indexer\IndexField($attribute, $forSorting);
        return $indexField->getFieldName();
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
        
        if ($this->isCategoryPage() && !$this->isCategoryDisplayActive()) {
            return false;
        }

        if (!$this->isSolrResultPage()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isSearchPage()
    {
        return Mage::app()->getRequest()->getModuleName() == 'catalogsearch'
            && Mage::app()->getRequest()->getControllerName() == 'result';
    }

    /**
     * @return bool
     */
    public function isCategoryPage()
    {
        return (Mage::app()->getRequest()->getModuleName() == 'catalog' && Mage::app()->getRequest()->getControllerName() == 'category')
            || (Mage::app()->getRequest()->getModuleName() == 'solr' && Mage::app()->getRequest()->getControllerName() == 'category');
    }

    /**
     * @return bool
     */
    public function isSolrResultPage()
    {
        return Mage::app()->getRequest()->getModuleName() == 'catalogsearch'
        || Mage::app()->getRequest()->getModuleName() == 'solr'
        || $this->isCategoryPage();
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