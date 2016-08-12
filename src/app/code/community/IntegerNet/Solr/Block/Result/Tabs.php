<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Result_Tabs extends Mage_Core_Block_Template
{
    /**
     * @return bool
     * @todo implement
     */
    public function canShowTabs()
    {
        return true;
    }

    /**
     * @return int
     */
    public function getProductResultCount()
    {
        /** @var IntegerNet_Solr_Block_Result_List $productResultsBlock */
        $productResultsBlock = $this->getLayout()->getBlock('search_result_list');
        return $productResultsBlock->getFoundProductsCount();
    }
}