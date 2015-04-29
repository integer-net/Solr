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
        'integernet_solr/suggestion',
    );

    protected $_resourceModelIdentifiers = array(
        'integernet_solr/solr',
    );

    public function getTemplate()
    {
        return 'integernet/solr/autosuggest.phtml';
    }

    protected $_initialEnvironmentInfo = null;

    /**
     * Store Solr configuration in serialized text field so it can be accessed from autosuggest later
     */
    public function storeSolrConfig()
    {
        $config = array();
        foreach(Mage::app()->getStores(false) as $store) { /** @var Mage_Core_Model_Store $store */
            
            $this->_emulateStore($store->getId());
            
            $config[$store->getId()]['integernet_solr'] = Mage::getStoreConfig('integernet_solr');
            
            $templateFile = $this->getTemplateFile($store->getId());
            $config[$store->getId()]['template_filename'] = $templateFile;
            $store->setConfig('template_filename', $templateFile);
            $config[$store->getId()]['base_url'] = Mage::getUrl();

            $this->_addAttributeData($config, $store);
            
            $this->_stopStoreEmulation();
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

    /**
     * Get absolute path to template
     *
     * @param int $storeId
     * @return string
     */
    public function getTemplateFile($storeId)
    {
        $params = array(
            '_relative' => true,
            '_area' => 'frontend',
        );

        $templateName = Mage::getBaseDir('app') . DS . 'design' . DS . Mage::getDesign()->getTemplateFilename($this->getTemplate(), $params);

        $templateContents = file_get_contents($templateName);

        $targetDirname = Mage::getBaseDir('var') . DS . 'integernet_solr' . DS . 'store_' . $storeId;
        if (!is_dir($targetDirname)) {
            mkdir($targetDirname, 0777, true);
        }
        $targetFilename = $targetDirname . DS . 'autosuggest.phtml';
        file_put_contents($targetFilename, $templateContents);

        return $targetFilename;
    }

    /**
     * @param array $config
     * @param Mage_Core_Model_Store $store
     * @return array
     */
    protected function _addAttributeData(&$config, $store)
    {
        $autosuggestAttributeConfig = unserialize(Mage::getStoreConfig('integernet_solr/autosuggest/attribute_filter_suggestions'));
        $allowedAttributeCodes = array();
        foreach ($autosuggestAttributeConfig as $row) {
            $allowedAttributeCodes[] = $row['attribute_code'];
        }

        foreach (Mage::helper('integernet_solr')->getFilterableInSearchAttributes() as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $allowedAttributeCodes)) {
                continue;
            }
            $options = array();
            foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                $options[$option['value']] = $option['label'];
            }
            $config[$store->getId()]['attribute'][$attribute->getAttributeCode()] = array(
                'attribute_code' => $attribute->getAttributeCode(),
                'label' => $attribute->getStoreLabel(),
                'options' => $options,
            );
        }
    }

    /**
     * @param int $storeId
     * @throws Mage_Core_Exception
     */
    protected function _emulateStore($storeId)
    {
        $newLocaleCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId);
        Mage::app()->getLocale()->setLocaleCode($newLocaleCode);
        Mage::getSingleton('core/translate')->setLocale($newLocaleCode)->init(Mage_Core_Model_App_Area::AREA_FRONTEND, true);
        $this->_currentStoreId = $storeId;
        $this->_initialEnvironmentInfo = Mage::getSingleton('core/app_emulation')->startEnvironmentEmulation($storeId);
        $this->_isEmulated = true;
        Mage::getDesign()->setStore($storeId);
        Mage::getDesign()->setPackageName();
        $themeName = Mage::getStoreConfig('design/theme/default', $storeId);
        Mage::getDesign()->setTheme($themeName);
    }

    protected function _stopStoreEmulation()
    {
        if ($this->_initialEnvironmentInfo) {
            Mage::getSingleton('core/app_emulation')->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
        }
    }
}