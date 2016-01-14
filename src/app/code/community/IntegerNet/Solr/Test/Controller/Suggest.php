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
     * @singleton integernet_solr/result
     * @singleton integernet_solr/result_collection
     * @helper catalogsearch
     * @loadFixture catalog
     * @doNotIindexAll
     */
    public function shouldShowAutosuggestBox()
    {
        $this->dispatch('catalogsearch/ajax/suggest', ['_query' => ['q' => 'war']]);
        $this->assertResponseBodyContains('<ul class="searchwords">', 'Search term suggest container');
        $this->assertResponseBodyContains('><span class="highlight">war</span> of the', 'Search term suggest content');
        $this->assertResponseBodyContains('<div class="products-box">', 'Product suggest container');
        $this->assertResponseBodyContains("Herbert George Wells: The War of the Worlds", 'Product suggest content');
        $this->assertResponseBodyContains('<div class="categories-box">', 'Category suggest container');
        $this->assertResponseBodyContains('Science-Fiction', 'Category suggest content');
    }
}