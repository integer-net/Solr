<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
final class IntegerNet_Solr_Autosuggest_Config
{
    protected $_config = null;
    protected $_storeId = null;

    public function __construct($storeId)
    {
        $this->_storeId = $storeId;
        $this->_config = $this->getConfigAsArray($storeId);
    }

    public function getStoreId()
    {
        return $this->_storeId;
    }

    public function getConfigData($path)
    {
        if (!isset($this->_config[$this->_storeId])) {
            throw new Exception('Config Data for store not found.');
        }
        $config = $this->_config[$this->_storeId];
        foreach(explode('/', $path) as $pathPart) {
            if (!isset($config[$pathPart])) {
                return null;
            }
            $config = $config[$pathPart];
        }

        return $config;
    }

    public function getModelClassname($identifier)
    {
        if (isset($this->_config['model'][$identifier])) {
            return $this->_config['model'][$identifier];
        }
        return '';
    }

    public function getResourceModelClassname($identifier)
    {
        if (isset($this->_config['resource_model'][$identifier])) {
            return $this->_config['resource_model'][$identifier];
        }
        return '';
    }

    /**
     * @param $storeId
     * @return bool|mixed
     */
    public function getConfigAsArray($storeId)
    {
        if (!($config = $this->_getConfigFromFile())) {
            $this->_createConfigFile($storeId);
            $config = $this->_getConfigFromFile();
        }

        return $config;
    }

    protected function _getConfigFromFile()
    {
        $config = @file_get_contents('var' . DIRECTORY_SEPARATOR . 'integernet_solr' . DIRECTORY_SEPARATOR . 'store_' . $this->_storeId . DIRECTORY_SEPARATOR . 'config.txt');
        if ($config === false) {
            return false;
        }

        $config = @unserialize($config);
        if ($config === false) {
            return false;
        }

        return $config;
    }

    protected function _getModelConfig()
    {

    }

    /**
     * @param $storeId
     */
    protected function _createConfigFile($storeId)
    {
        require_once 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
        umask(0);
        Mage::app()->setCurrentStore($storeId);
        Mage::helper('integernet_solr/autosuggest')->storeSolrConfig();
    }
}