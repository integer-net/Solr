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

        $this->initAutoload();

        $storeId = intval($_GET['store_id']);
        Mage::app()->setCurrentStore($storeId);

        $newLocaleCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId);
        Mage::app()->getLocale()->setLocaleCode($newLocaleCode);
        Mage::getSingleton('core/translate')->setLocale($newLocaleCode)->init(Mage_Core_Model_App_Area::AREA_FRONTEND, true);
    }

    private function initAutoload()
    {
        $autoloader = new IntegerNet_Solr_Helper_Autoloader();
        $autoloader->createAndRegister();
    }

    public function getHtml()
    {
        if (!isset($_GET['q'])) {
            die('Query not given.');
        }

        Mage::register('is_autosuggest', true);
        $block = Mage::app()->getLayout()->createBlock('integernet_solrpro/autosuggest');

        return $block->toHtml();
    }
}

require_once 'app/Mage.php';
umask(0);

$autosuggest = new IntegerNet_Solr_Autosuggest();

echo $autosuggest->getHtml();