<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * @loadFixture registry
 * @loadFixture config
 */
class IntegerNet_SolrPro_Test_Controller_Suggest extends IntegerNet_Solr_Test_Controller_Abstract
{

    /**
     * @test
     * @dataProvider dataAutoSuggestBox
     * @loadFixture catalog
     */
    public function shouldShowAutosuggestBox($config, $expectedInBody)
    {
        $this->reindexWithConfig([
            'integernet_solr/category/is_indexer_active' => 0
        ]);
        $this->setCurrentStore('default');
        $this->applyConfig($config);

        $this->dispatch('catalogsearch/ajax/suggest', ['_query' => ['q' => 'war']]);
        $this->assertResponseBodyContains('<ul class="searchwords">', 'Search term suggest container');
        $this->assertResponseBodyContains('><span class="highlight">war</span> of the', 'Search term suggest content');
        $this->assertResponseBodyContains('<div class="products-box">', 'Product suggest container');
        $this->assertResponseBodyContains("Herbert George Wells: The War of the Worlds", 'Product suggest content');
        $this->assertResponseBodyContains('<div class="categories-box">', 'Category suggest container');
        $this->assertResponseBodyContains('Science-Fiction', 'Category suggest content');
        $this->assertResponseBodyContains('<div class="attributes-box">', 'Attribute container');
        $this->assertResponseBodyContains('<strong>Manufacturer1</strong>', 'Attribute container content');
        $this->assertResponseBodyContains('/catalogsearch/result/?manufacturer=5&amp;q=war">Herbert George Wells</a>', 'Link to attribute search');

        foreach ($expectedInBody as $expected) {
            $this->assertResponseBodyContains($expected);
        }
    }
    /**
     * @test
     * @loadFixture catalog
     */
    public function shouldShowAutosuggestBoxWithCategoryIndexer()
    {
        $this->reindexWithConfig([
            'integernet_solr/category/is_indexer_active' => 1
        ]);
        $this->setCurrentStore('default');

        $this->dispatch('catalogsearch/ajax/suggest', ['_query' => ['q' => 'Science']]);
        $this->assertResponseBodyContains('<div class="categories-box">', 'Category suggest container');
        $this->assertResponseBodyContains('<span class="highlight">Science</span>-Fiction', 'Category suggest content');
    }

    /**
     * @test
     * @loadFixture catalog
     */
    public function shouldShowAutosuggestBoxLocalized()
    {
        $this->reindexWithConfig([]);
        // We need to reload the attribute config in store scope, has been loaded during reindex,
        // in admin scope, so that attribute labels are missing
        Mage::unregister('_singleton/eav/config');

        $this->dispatch('catalogsearch/ajax/suggest', ['_query' => ['q' => 'Hodor'], '_store' => 'store2']);
        $this->assertEquals(2, Mage::app()->getStore()->getId());
        $this->assertResponseBodyContains('<ul class="searchwords">', 'Search term suggest container');
        $this->assertResponseBodyContains('><span class="highlight">Hodor</span> <span class="highlight">Hodor</span> <span class="highlight">Hodor</span>', 'Search term suggest content');
        $this->assertResponseBodyContains('<div class="products-box">', 'Product suggest container');
        $this->assertResponseBodyContains('Hodor Hodor Hodor: Hodor of the Hodor', 'Product suggest content');
        $this->assertResponseBodyContains('<div class="attributes-box">', 'Attribute container');
        $this->assertResponseBodyContains('<strong>Manufacturer2</strong>', 'Attribute container content');
        $this->assertResponseBodyContains('/catalogsearch/result/?manufacturer=5&amp;q=Hodor"><span class="highlight">Hodor</span> <span class="highlight">Hodor</span> <span class="highlight">Hodor</span></a>', 'Link to attribute search');
    }

    /**
     * Data provider
     */
    public static function dataAutoSuggestBox()
    {
        return [
            'category_link_type_direct' => [
                'config' => [
                    'integernet_solr/autosuggest/category_link_type' => \IntegerNet\Solr\Config\AutosuggestConfig::CATEGORY_LINK_TYPE_DIRECT
                ],
                'expectedInBody' => [
                    'catalog/category/view/s/sci-fi/id/22/'
                ]
            ],
            'category_link_type_filter' => [
                'config' => [
                    'integernet_solr/autosuggest/category_link_type' => \IntegerNet\Solr\Config\AutosuggestConfig::CATEGORY_LINK_TYPE_FILTER
                ],
                'expectedInBody' => [
                    'catalogsearch/result/?cat=22&q=war'
                ]
            ],
        ];
    }

    /**
     * @param $config
     */
    private function applyConfig($config)
    {
        foreach ($config as $path => $value) {
            $this->app()->getStore()->setConfig($path, $value);
        }
        // We need to reload the attribute config in store scope, has been loaded during reindex,
        // in admin scope, so that attribute labels are missing
        Mage::unregister('_singleton/eav/config');
    }

    /**
     * @param $config
     */
    private function reindexWithConfig($config)
    {
        $this->applyConfig($config);
        Mage::getModel('integernet_solr/indexer')->reindexAll();
    }
}