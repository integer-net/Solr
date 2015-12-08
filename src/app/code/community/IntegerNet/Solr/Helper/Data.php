<?php
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\HasUserQuery;
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Helper_Data extends Mage_Core_Helper_Abstract
    implements EventDispatcher, HasUserQuery
{

    /**
     * @deprecated use repository directly
     * @return Attribute[]
     */
    public function getSearchableAttributes()
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getSearchableAttributes();
    }

    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableAttributes($useAlphabeticalSearch);
    }
    
    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableInSearchAttributes($useAlphabeticalSearch);
    }


    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableInCatalogAttributes($useAlphabeticalSearch);

    }

    /**
     * @deprecated use repository directly
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
    {
        return Mage::getSingleton('integernet_solr/bridge_attributeRepository')
            ->getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch);
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
     * @todo move to lib
     * @param Attribute $attribute
     * @param bool $forSorting
     * @return string
     */
    public function getFieldName($attribute, $forSorting = false)
    {
        if ($attribute->getUsedForSortBy()) {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f';

                case 'text':
                    return $attribute->getAttributeCode() . '_t';

                default:
                    return ($forSorting) ? $attribute->getAttributeCode() . '_s' : $attribute->getAttributeCode() . '_t';
            }
        } else {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f_mv';

                case 'text':
                    return $attribute->getAttributeCode() . '_t_mv';

                default:
                    return $attribute->getAttributeCode() . '_t_mv';
            }
        }
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

        return true;
    }

    /**
     * @return bool
     */
    public function isCategoryPage()
    {
        return Mage::app()->getRequest()->getModuleName() == 'catalog'
            && Mage::app()->getRequest()->getControllerName() == 'category';
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

    /**
     * Dispatch event
     *
     * @param string $eventName
     * @param array $data
     * @return void
     */
    public function dispatch($eventName, array $data = [])
    {
        Mage::dispatchEvent($eventName, $data);
    }

    /**
     * Returns query as entered by user
     *
     * @return string
     */
    public function getUserQueryText()
    {
        $query = Mage::helper('catalogsearch')->getQuery();
        $queryText = $query->getQueryText();
        if ($query->getSynonymFor()) {
            $queryText = $query->getSynonymFor();
            return $queryText;
        }
        return $queryText;
    }

}