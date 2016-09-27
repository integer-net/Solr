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
class IntegerNet_SolrPro_Helper_Autosuggest extends Mage_Core_Helper_Abstract
    implements TemplateRepository, SerializableCategoryRepository
{
    /**
     * @var IntegerNet_Solr_Model_Bridge_StoreEmulation
     */
    protected $_storeEmulation;

    public function __construct()
    {
        $this->_storeEmulation = Mage::getModel('integernet_solr/bridge_factory')->createStoreEmulation();
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
        $factory = Mage::helper('integernet_solrpro')->factory();
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
     * @param $storeId
     * @return array
     */
    protected function _getCategoriesData($storeId)
    {
        $categoryData = array();
        $maxNumberCategories = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_category_suggestions', $storeId));
        if (!$maxNumberCategories) {
            return $categoryData;
        }

        /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
        $categories = Mage::getResourceModel('catalog/category_collection');
        $categories->setStoreId($storeId);
        $categories->addAttributeToSelect(array('name', 'url_key'));
        $categories->addAttributeToFilter('is_active', 1);
        $categories->addAttributeToFilter('include_in_menu', 1);

        /*
         * Unfortunately there is not sane way to tell the category URL model to not use the SID parameter,
         * if current host and store host differ, so we have to switch the feature off globally while category URLs are generated.
         */
        $useSid = Mage::app()->getUseSessionInUrl();
        Mage::app()->setUseSessionInUrl(false);

        foreach($categories as $category) {
            /** @var Mage_Catalog_Model_Category $category */
            $category->setStoreId($storeId);
            $category->getUrlInstance()->setStore($storeId); // for URLs without rewrite
            $category->getUrlModel()->getUrlInstance()->setStore($storeId); // for URLs with rewrites
            $categoryData[$category->getId()] = array(
                'id' => $category->getId(),
                'title' => $this->_getCategoryTitle($category),
                'url' => $category->getUrl(),
            );
        }

        Mage::app()->setUseSessionInUrl($useSid);
        return $categoryData;
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

    /**
     * @param int $storeId
     * @return \IntegerNet\SolrSuggest\Implementor\SerializableSuggestCategory[]
     */
    public function findActiveCategories($storeId)
    {
        return array_map(function(array $categoryConfig) {
            return new Category($categoryConfig['id'], $categoryConfig['title'], $categoryConfig['url']);
        }, $this->_getCategoriesData($storeId));
    }

}