<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrVitex
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrVitex_Model_Observer
{
    public function integernetSolrFilterItemCreate(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('integernet_solr')->isCategoryPage()) {
            return;
        }
        
        /** @var Varien_Object $item */
        $item = $observer->getItem();
        if ($observer->getType() == 'category') {
            /** @var Mage_Catalog_Model_Category $category */
            if ($category = $observer->getEntity()) {
                $item->setUrl($category->getUrl());
            }
        }
    }
}