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
    public function adminhtmlCatalogProductAttributeEditPrepareForm(Varien_Event_Observer $observer)
    {
        /* @var $fieldset Varien_Data_Form_Element_Fieldset */
        $fieldset = $observer->getForm()->getElement('front_fieldset');

        $field = $fieldset->addField('solr_boost', 'text', array(
            'name'      => 'solr_boost',
            'label'     => Mage::helper('integernet_solr')->__('Solr Boost'),
            'title'     => Mage::helper('integernet_solr')->__('Solr Boost'),
            'note'     => Mage::helper('integernet_solr')->__('1 is default, use higher numbers for higher priority.'),
            'class'     => 'validate-number',
        ));
        
        // Set default value
        $field->setValue('1.0000');
    }
}