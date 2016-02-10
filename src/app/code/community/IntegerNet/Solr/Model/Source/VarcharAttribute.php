<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 

class IntegerNet_Solr_Model_Source_VarcharAttribute
{
    const SEARCH_OPERATOR_AND = 'AND';
    const SEARCH_OPERATOR_OR = 'OR';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(array(
            'value' => '',
            'label' => '',
        ));
        $attributes = Mage::getSingleton('integernet_solr/bridge_attributeRepository')->getVarcharProductAttributes();

        foreach($attributes as $attribute) { /** @var Mage_Catalog_Model_Entity_Attribute $attribute */
            $options[] = array(
                'value' => $attribute->getAttributeCode(),
                'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
            );
        }
        return $options;
    }
}