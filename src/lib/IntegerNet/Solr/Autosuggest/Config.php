<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Autosuggest_Config
{
    protected $_config = null;
    protected $_storeId = null;

    public function __construct($storeId)
    {
        $this->_config = $this->getConfigAsArray($storeId);
        $this->_storeId = $storeId;
    }

    public function getConfigData($path)
    {
        $config = $this->_config;
        foreach(explode('/', $path) as $pathPart) {
            if (!isset($config[$pathPart])) {
                return null;
            }
            $config = $config[$pathPart];
        }

        return $config;
    }

    /**
     * @param $storeId
     * @return bool|mixed
     */
    public function getConfigAsArray($storeId)
    {
        if (!($config = $this->_getConfigFromFile($storeId))) {
            $this->_createConfigFile($storeId);
            $config = Mage::getStoreConfig('integernet_solr', $storeId);
        }
        $config = array('integernet_solr' => $config);

        return $config;
    }

    protected function _getConfigFromFile($storeId)
    {
        $config = file_get_contents('var' . DS . 'integernet_solr' . DS . 'config.txt');
        if ($config === false) {
            return false;
        }

        $config = @unserialize($config);
        if ($config === false) {
            return false;
        }

        if (!isset($config[$storeId])) {
            return false;
        }

        return $config[$storeId];
    }

    /**
     * @param $storeId
     */
    protected function _createConfigFile($storeId)
    {
        require_once 'app' . DS . 'Mage.php';
        umask(0);
        Mage::app()->setCurrentStore($storeId);
        Mage::helper('integernet_solr/autosuggest')->storeSolrConfig();
    }
}