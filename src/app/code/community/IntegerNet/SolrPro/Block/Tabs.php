<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Block_Tabs extends Mage_Core_Block_Template
{
    /**
     * @return bool
     */
    public function canShowTabs()
    {
        return $this->getCmsResultCount() + $this->getCategoryResultCount() > 0;
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

    /**
     * @return int
     */
    public function getCmsResultCount()
    {
        /** @var IntegerNet_SolrPro_Block_Result_Cms $cmsResultsBlock */
        $cmsResultsBlock = $this->getLayout()->getBlock('catalogsearch.solr.tab.cms');
        return $cmsResultsBlock->getResultsCollection()->getSize();
    }

    /**
     * @return int
     */
    public function getCategoryResultCount()
    {
        /** @var IntegerNet_SolrPro_Block_Result_Cms $categoriesResultsBlock */
        $categoriesResultsBlock = $this->getLayout()->getBlock('catalogsearch.solr.tab.categories');
        return $categoriesResultsBlock->getResultsCollection()->getSize();
    }

    public function getActiveTabName()
    {
        if($this->getProductResultCount()) {
            return 'solr_tab_link_products';
        }
        if($this->getCategoryResultCount()) {
            return 'solr_tab_link_categories';
        }
        return 'solr_tab_link_cms';
    }
}