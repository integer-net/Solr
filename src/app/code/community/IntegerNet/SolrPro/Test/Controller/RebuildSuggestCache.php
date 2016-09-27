<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
/**
 * @loadFixture registry
 * @loadFixture config
 */
class IntegerNet_SolrPro_Test_Controller_RebuildSuggestCache extends IntegerNet_Solr_Test_Controller_Abstract
{
    /**
     * @test
     * @loadFixture catalog
     */
    public function categoryCacheShouldBeBuiltWithStoreUrls()
    {
        $comedyCategoryId = 21;

        $this->adminSession();

        Mage::helper('integernet_solrpro')->autosuggest()->storeSolrConfig();
        $cachedCategories = Mage::helper('integernet_solrpro')->factory()->getCacheReader()->getActiveCategories(2);
        $this->assertNotEmpty($cachedCategories);
        $this->assertNotEmpty($cachedCategories[$comedyCategoryId]);
        /** @var \IntegerNet\SolrSuggest\Implementor\SerializableSuggestCategory $cachedCategory */
        $cachedCategory = $cachedCategories[$comedyCategoryId];
        $this->assertEquals('Comedy Store', $cachedCategory->getName(), 'Store specific name');
        $this->assertRegExp('{comedy-store}', $cachedCategory->getUrl(), 'Store specific URL key');
        $this->assertRegExp('{store2\.magento\.local}', $cachedCategory->getUrl(), 'Store specific base URL');
        $this->assertNotRegExp('{SID=}', $cachedCategory->getUrl(), 'URL without SID parameter');
    }
}