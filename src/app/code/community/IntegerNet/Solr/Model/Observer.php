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

    public function adminSystemConfigChangedSectionIntegernetSolr(Varien_Event_Observer $observer)
    {
        $storeId = null;
        if ($storeCode = $observer->getStore()) {

            $storeId = Mage::app()->getStore($storeCode)->getId();
        }
        if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
            return;
        }

        if (!Mage::getStoreConfig('integernet_solr/server/host', $storeId)) {
            return;
        }

        $solr = Mage::getResourceModel('integernet_solr/solr')->getSolr($storeId);

        if (!$solr->ping()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('integernet_solr')->__('Solr Connection could not be established.'));
        } else {
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('integernet_solr')->__('Solr Connection established.'));
        }
    }
}