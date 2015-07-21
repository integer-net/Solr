<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Model_Observer
{
    /**
     * Add new field "solr_boost" to attribute form
     * 
     * @param Varien_Event_Observer $observer
     */
    public function adminhtmlCatalogProductAttributeEditPrepareForm(Varien_Event_Observer $observer)
    {
        /* @var $fieldset Varien_Data_Form_Element_Fieldset */
        $fieldset = $observer->getForm()->getElement('front_fieldset');

        $field = $fieldset->addField('solr_boost', 'text', array(
            'name'      => 'solr_boost',
            'label'     => Mage::helper('integernet_solr')->__('Solr Priority'),
            'title'     => Mage::helper('integernet_solr')->__('Solr Priority'),
            'note'     => Mage::helper('integernet_solr')->__('1 is default, use higher numbers for higher priority.'),
            'class'     => 'validate-number',
        ));
        
        // Set default value
        $field->setValue('1.0000');
    }

    /**
     * Add new column "solr_boost" to attribute grid
     * 
     * @param Varien_Event_Observer $observer
     */
    public function coreBlockAbstractToHtmlBefore(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        
        if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Attribute_Grid) {

            $block->addColumnAfter('solr_boost', array(
                'header' => Mage::helper('catalog')->__('Solr Priority'),
                'sortable' => true,
                'index' => 'solr_boost',
                'type' => 'number',
            ), 'is_comparable');
        }
    }

    /**
     * Check Solr connection on config save
     * 
     * @param Varien_Event_Observer $observer
     */
    public function adminSystemConfigChangedSectionIntegernetSolr(Varien_Event_Observer $observer)
    {
        Mage::helper('integernet_solr/autosuggest')->storeSolrConfig();
    }

    public function controllerActionPredispatchCatalogsearchResultIndex(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Varien_Action $action */
        $action = $observer->getControllerAction();
        
        if (Mage::getStoreConfigFlag('integernet_solr/general/is_active') && $order = $action->getRequest()->getParam('order')) {
            if ($order === 'relevance') {
                $_GET['order'] = 'position';
            }
        }

        Mage::app()->getStore()->setConfig(Mage_Catalog_Model_Config::XML_PATH_LIST_DEFAULT_SORT_BY, 'position');
    }

    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        /** @var $indexer Mage_Index_Model_Process */
        $indexer = Mage::getModel('index/process')->load('integernet_solr', 'indexer_code');
        if ($indexer->getMode() != Mage_Index_Model_Process::MODE_REAL_TIME) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = $observer->getProduct();
            Mage::getSingleton('integernet_solr/indexer_product')->deleteIndex(array($product->getId()));
        }
    }

    /**
     * Regenerate config if all cache should be deleted.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function applicationCleanCache(Varien_Event_Observer $observer)
    {
        $tags = $observer->getTags();
        if (!is_array($tags) || sizeof($tags)) {
            return;
        }
        Mage::helper('integernet_solr/autosuggest')->storeSolrConfig();
    }
}