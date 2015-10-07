<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Result_Layer extends Mage_Core_Block_Abstract
{
    /**
     * @return IntegerNet_Solr_Block_Result_Layer_State
     */
    public function getState()
    {
        if ($block = $this->getLayout()->getBlock('catalogsearch.layer.state')) {
            return $block;
        }

        return $block = $this->getLayout()->getBlock('catalog.layer.state');
    }
}