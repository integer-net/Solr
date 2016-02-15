<?php
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\SolrSuggest\Implementor\SerializableAttributeRepository;
use IntegerNet\SolrSuggest\Implementor\SerializableCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\TemplateRepository;
use IntegerNet\SolrSuggest\Plain\Block\Template;
use IntegerNet\SolrSuggest\Plain\Bridge\Attribute;
use IntegerNet\SolrSuggest\Plain\Bridge\Category;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Helper_Autosuggest extends Mage_Core_Helper_Abstract
    implements TemplateRepository, SerializableAttributeRepository, SerializableCategoryRepository
{
    protected $_modelIdentifiers = array(
        'integernet_solr/suggestion_collection',
        'integernet_solr/result',
        'integernet_solr/query',
        'integernet_solr/result_pagination_autosuggest',
        'integernet_solr/suggestion',
    );

    protected $_resourceModelIdentifiers = array(
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
        $factory = Mage::helper('integernet_solr/factory');
        $factory->getCacheWriter()->write($factory->getStoreConfig());
    }

    /**
     * @param int $storeId
     * @return Template
     */
    public function getTemplateByStoreId($storeId)
    {
        $initialStoreId = Mage::app()->getStore()->getId();
        $this->_emulateStore($storeId);

        $template = new Template($this->getTemplateFile($storeId));

        $this->_emulateStore($initialStoreId);
        $this->_stopStoreEmulation();

        return $template;
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

        $templateContents = $this->_getTranslatedTemplate($templateContents);

        $targetDirname = Mage::getBaseDir('cache') . DS . 'integernet_solr' . DS . 'store_' . $storeId;
        if (!is_dir($targetDirname)) {
            mkdir($targetDirname, 0777, true);
        }
        $targetFilename = $targetDirname . DS . 'autosuggest.phtml';
        file_put_contents($targetFilename, $templateContents);

        return $targetFilename;
    }

    /**
     * @param array $config
     * @param $storeId
     */
    protected function _addAttributeData(&$config, $storeId)
    {
        $autosuggestAttributeConfig = unserialize(Mage::getStoreConfig('integernet_solr/autosuggest/attribute_filter_suggestions'));
        $allowedAttributeCodes = array();
        foreach ($autosuggestAttributeConfig as $row) {
            $allowedAttributeCodes[] = $row['attribute_code'];
        }

        $config[$storeId]['attribute'] = array();
        foreach (Mage::helper('integernet_solr')->getFilterableInSearchAttributes() as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $allowedAttributeCodes)) {
                continue;
            }
            $options = array();
            foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                $options[$option['value']] = $option['label'];
            }
            $config[$storeId]['attribute'][$attribute->getAttributeCode()] = array(
                'attribute_code' => $attribute->getAttributeCode(),
                'label' => $attribute->getStoreLabel(),
                'options' => $options,
            );
        }

        $config[$storeId]['searchable_attribute'] = array();
        foreach (Mage::helper('integernet_solr')->getSearchableAttributes() as $attribute) {
            $config[$storeId]['searchable_attribute'][$attribute->getAttributeCode()] = array(
                'attribute_code' => $attribute->getAttributeCode(),
                'label' => $attribute->getStoreLabel(),
                'solr_boost' => $attribute->getSolrBoost(),
                'used_for_sortby' => $attribute->getUsedForSortBy(),
            );
        }
    }

    /**
     * @param array $config
     * @param $storeId
     */
    public function _addCategoriesData(&$config, $storeId)
    {
        $maxNumberCategories = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_category_suggestions'));
        if (!$maxNumberCategories) {
            return;
        }

        $categories = Mage::getResourceModel('catalog/category_collection')
            ->addAttributeToSelect(array('name', 'url_key'))
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('include_in_menu', 1);

        foreach($categories as $category) {
            $config[$storeId]['categories'][$category->getId()] = array(
                'id' => $category->getId(),
                'title' => $this->_getCategoryTitle($category),
                'url' => $category->getUrl(),
            );
        }
    }



    /**
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    protected function _getCategoryUrl($category)
    {
        $linkType = Mage::getStoreConfig('integernet_solr/autosuggest/category_link_type');
        if (false && $linkType == AutosuggestConfig::CATEGORY_LINK_TYPE_FILTER) {
            return Mage::getUrl('catalogsearch/result', array(
                '_query' => array(
                    'q' => $this->escapeHtml($this->getQuery()),
                    'cat' => $category->getId()
                )
            ));
        }

        return $category->getUrl();
    }

    /**
     * Return category name or complete path, depending on what is configured
     *
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    protected function _getCategoryTitle($category)
    {
        if (Mage::getStoreConfigFlag('integernet_solr/autosuggest/show_complete_category_path')) {
            $categoryPathIds = $category->getPathIds();
            array_shift($categoryPathIds);
            array_shift($categoryPathIds);
            array_pop($categoryPathIds);

            $categoryPathNames = array();
            foreach($categoryPathIds as $categoryId) {
                $categoryPathNames[] = Mage::getResourceSingleton('catalog/category')->getAttributeRawValue($categoryId, 'name', Mage::app()->getStore()->getId());
            }
            $categoryPathNames[] = $category->getName();
            return implode(' > ', $categoryPathNames);
        }
        return $category->getName();
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

    /**
     * Translate all occurences of $this->__('...') with translated text
     *
     * @param string $templateContents
     * @return string
     */
    protected function _getTranslatedTemplate($templateContents)
    {
        preg_match_all('$->__\(\'(.*)\'$', $templateContents, $results);

        foreach($results[1] as $key => $search) {

            $replace = Mage::helper('integernet_solr')->__($search);
            $templateContents = str_replace($search, $replace, $templateContents);
        }

        return $templateContents;
    }

    protected $_configForCache = array();
    /**
     * @param int $storeId
     * @return \IntegerNet\SolrSuggest\Implementor\SerializableAttribute[]
     */
    public function findFilterableInSearchAttributes($storeId)
    {
        if (! isset($this->_configForCache[$storeId]['attribute'])) {
            $this->_addAttributeData($this->_configForCache, $storeId);
        }
        return array_map(function(array $attributeConfig) {
            return new Attribute($attributeConfig);
        }, $this->_configForCache[$storeId]['attribute']);
    }

    /**
     * @param $storeId
     * @return \IntegerNet\SolrSuggest\Implementor\SerializableAttribute[]
     */
    public function findSearchableAttributes($storeId)
    {
        if (! isset($this->_configForCache[$storeId]['searchable_attribute'])) {
            $this->_addAttributeData($this->_configForCache, $storeId);
        }
        return array_map(function(array $attributeConfig) {
            return new Attribute($attributeConfig);
        }, $this->_configForCache[$storeId]['searchable_attribute']);
    }


    /**
     * @param int $storeId
     * @return \IntegerNet\SolrSuggest\Implementor\SerializableCategory[]
     */
    public function findActiveCategories($storeId)
    {
        if (! isset($this->_configForCache[$storeId]['categories'])) {
            $this->_addCategoriesData($this->_configForCache, $storeId);
        }
        return array_map(function(array $categoryConfig) {
            return new Category($categoryConfig['id'], $categoryConfig['title'], $categoryConfig['url']);
        }, $this->_configForCache[$storeId]['categories']);
    }

}