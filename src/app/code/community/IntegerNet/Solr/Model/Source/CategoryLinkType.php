<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 

class IntegerNet_Solr_Model_Source_CategoryLinkType
{
    const CATEGORY_LINK_TYPE_FILTER = 'filter';
    const CATEGORY_LINK_TYPE_DIRECT = 'direct';
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::CATEGORY_LINK_TYPE_FILTER, 
                'label' => Mage::helper('integernet_solr')->__('Search result page with set category filter')
            ),
            array(
                'value' => self::CATEGORY_LINK_TYPE_DIRECT, 
                'label' => Mage::helper('integernet_solr')->__('Category page')
            ),
        );
    }
}