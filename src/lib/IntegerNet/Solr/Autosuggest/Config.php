<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace {

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
}
/*
 * Dummy interfaces to be able to instantiate Magento helper without loaded configuration
 *
 * storeSolrConfig() needs it to translate phtml template
 *
 * Will be obsolete as soon as writing the config cache is moved to the module
 */
namespace IntegerNet\Solr\Implementor {
    if (! interface_exists('IntegerNet\\Solr\\Implementor\\Attribute', false)) {
        interface HasUserQuery {}
        interface EventDispatcher {}
    }
}
namespace IntegerNet\SolrSuggest\Implementor {
    if (! interface_exists('IntegerNet\\SolrSuggest\\Implementor\\SearchUrl', false)) {
        interface SearchUrl {}
    }
}