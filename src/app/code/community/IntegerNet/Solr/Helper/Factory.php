<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Helper_Factory implements IntegerNet_Solr_Interface_Factory
{
    public function __construct()
    {
        $this->registerAutoloader();
    }
    /**
     * Returns new configured Solr recource
     *
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    public function getSolrResource()
    {
        $storeConfig = [];
        foreach (Mage::app()->getStores(true) as $store) {
            /** @var Mage_Core_Model_Store $store */
            if ($store->getIsActive()) {
                $storeConfig[$store->getId()] = new IntegerNet_Solr_Model_Config_Store($store->getId());
            }
        }
        return new IntegerNet_Solr_Model_Resource_Solr($storeConfig);
    }

    /**
     * Returns new Solr result wrapper
     *
     * @return IntegerNet_Solr_Test_Model_Result
     */
    public function getSolrResult()
    {
        return Mage::getModel('integernet_solr/result');
    }


    public function registerAutoloader()
    {
        if (Mage::getStoreConfigFlag('integernet_solr/dev/register_autoloader')) {
            $libBaseDir = Mage::getStoreConfig('integernet_solr/dev/autoloader_basepath');
            if ($libBaseDir[0] !== '/') {
                $libBaseDir = Mage::getBaseDir() . DS . $libBaseDir;
            }
            Mage::helper('integernet_solr/autoloader')->addNamespace('IntegerNet\Solr', $libBaseDir, true)->register();
        }
    }
}