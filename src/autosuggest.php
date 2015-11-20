<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

class IntegerNet_Solr_Autosuggest
{
    public function __construct()
    {
        if (!isset($_GET['store_id'])) {
            die('Store ID not given.');
        }

        $storeId = intval($_GET['store_id']);

        $config = $this->_getConfig($storeId);

        if (!class_exists('Mage')) {
            require_once('lib' . DIRECTORY_SEPARATOR . 'IntegerNet' . DIRECTORY_SEPARATOR . 'Solr' . DIRECTORY_SEPARATOR . 'Autosuggest' . DIRECTORY_SEPARATOR . 'Mage.php');
            class_alias('IntegerNet_Solr_Autosuggest_Mage', 'Mage');
            Mage::setConfig($config);

            require_once('lib' . DIRECTORY_SEPARATOR . 'IntegerNet' . DIRECTORY_SEPARATOR . 'Solr' . DIRECTORY_SEPARATOR . 'Autosuggest' . DIRECTORY_SEPARATOR . 'Empty.php');
            class_alias('IntegerNet_Solr_Autosuggest_Empty', 'Mage_Core_Model_Resource_Abstract');
        }
        IntegerNet_Solr_Helper_Autoloader::createAndRegister();

/*        $newLocaleCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId);
        Mage::app()->getLocale()->setLocaleCode($newLocaleCode);
        Mage::getSingleton('core/translate')->setLocale($newLocaleCode)->init(Mage_Core_Model_App_Area::AREA_FRONTEND, true);*/
    }
    
    public function printHtml()
    {
        if (!isset($_GET['q'])) {
            die('Query not given.');
        }

        $block = new IntegerNet_Solr_Autosuggest_Result();

        return $block->printHtml();
    }

    /**
     * @return IntegerNet_Solr_Autosuggest_Config
     */
    protected function _getConfig($storeId)
    {
        require_once('lib' . DIRECTORY_SEPARATOR . 'IntegerNet' . DIRECTORY_SEPARATOR . 'Solr' . DIRECTORY_SEPARATOR . 'Autosuggest' . DIRECTORY_SEPARATOR . 'Config.php');
        return new IntegerNet_Solr_Autosuggest_Config($storeId);
    }
}

$autosuggest = new IntegerNet_Solr_Autosuggest();

$autosuggest->printHtml();