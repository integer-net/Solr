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
 * @loadFixture config
 */
class IntegerNet_Solr_Test_Controller_Suggest extends IntegerNet_Solr_Test_Controller_Abstract
{

    protected function setUp()
    {
        parent::setUp();
        Mage::getModel('integernet_solr/indexer')->reindexAll();
    }

    /**
     * @test
     * @singleton core/session
     * @singleton catalog/session
     * @singleton customer/session
     * @singleton reports/session
     * @singleton integernet_solr/bridge_attributeRepository
     * @singleton integernet_solr/bridge_categoryRepository
     * @singleton integernet_solr/result
     * @singleton integernet_solr/result_collection
     * @helper catalogsearch
     * @loadFixture catalog
     * @doNotIindexAll
     */
    public function shouldShowAutosuggestBox()
    {
        $this->setCurrentStore('default');
        // We need to reload the attribute config in store scope, has been loaded during reindex,
        // in admin scope, so that attribute labels are missing
        Mage::unregister('_singleton/eav/config');

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
    }

    /**
     * @test
     * @singleton core/session
     * @singleton catalog/session
     * @singleton customer/session
     * @singleton reports/session
     * @singleton integernet_solr/bridge_attributeRepository
     * @singleton integernet_solr/bridge_categoryRepository
     * @singleton integernet_solr/result
     * @singleton integernet_solr/result_collection
     * @helper catalogsearch
     * @loadFixture catalog
     * @doNotIindexAll
     */
    public function shouldShowAutosuggestBoxLocalized()
    {
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
}