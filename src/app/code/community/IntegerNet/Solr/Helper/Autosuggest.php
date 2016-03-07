<?php
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\SolrSuggest\Implementor\SerializableCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\TemplateRepository;
use IntegerNet\SolrSuggest\Plain\Block\Template;
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
    implements TemplateRepository, SerializableCategoryRepository
{
    /**
     * @var IntegerNet_Solr_Model_Bridge_StoreEmulation
     */
    protected $_storeEmulation;

    public function __construct()
    {
        $this->_storeEmulation = Mage::getModel('integernet_solr/bridge_storeEmulation');
    }


    public function getTemplate()
    {
        return 'integernet/solr/autosuggest.phtml';
    }

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
        $this->_storeEmulation->start($storeId);
        $template = new Template($this->getTemplateFile($storeId));
        $this->_storeEmulation->stop();
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