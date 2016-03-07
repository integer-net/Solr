<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 

class IntegerNet_Solr_Model_Source_FilterPosition
{
    const FILTER_POSITION_DEFAULT = 0;
    const FILTER_POSITION_LEFT = 1;
    const FILTER_POSITION_TOP = 2;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::FILTER_POSITION_LEFT,
                'label' => Mage::helper('integernet_solr')->__('Left column (Magento default)')
            ),
            array(
                'value' => self::FILTER_POSITION_TOP,
                'label' => Mage::helper('integernet_solr')->__('Content column (above products)')
            ),
        );
    }
}