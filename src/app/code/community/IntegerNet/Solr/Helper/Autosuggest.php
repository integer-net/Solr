<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Helper_Autosuggest extends Mage_Core_Helper_Abstract
{
    protected $_modelIdentifiers = array(
        'integernet_solr/suggestion_collection',
        'integernet_solr/result',
    );

    protected $_resourceModelIdentifiers = array(
        'integernet_solr/solr',
    );

    /**
     * Store Solr configuration in serialized text field so it can be accessed from autosuggest later
     */
    public function storeSolrConfig()
    {
        $config = array();
        foreach(Mage::app()->getStores(false) as $store) { /** @var Mage_Core_Model_Store $store */
            $config[$store->getId()]['integernet_solr'] = Mage::getStoreConfig('integernet_solr', $store);
        }

        foreach($this->_modelIdentifiers as $identifier) {
            $config['model'][$identifier] = get_class(Mage::getModel($identifier));
        }

        foreach($this->_resourceModelIdentifiers as $identifier) {
            $config['resource_model'][$identifier] = get_class(Mage::getResourceModel($identifier));
        }

        $filename = Mage::getBaseDir('var') . DS . 'integernet_solr' . DS . 'config.txt';
        file_put_contents($filename, serialize($config));
    }
}