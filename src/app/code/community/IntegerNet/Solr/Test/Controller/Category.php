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
class IntegerNet_Solr_Test_Controller_Category extends IntegerNet_Solr_Test_Controller_Abstract
{
    const CATEGORY_ID = 221;

    protected function setUp()
    {
        parent::setUp();
        Mage::getModel('integernet_solr/indexer')->reindexAll();
    }

    /**
     * @test
     * @registry current_category
     * @registry current_entity_key
     * @singleton catalog/layer
     * @singleton core/session
     * @singleton catalog/session
     * @singleton customer/session
     * @singleton reports/session
     * @singleton integernet_solr/bridge_attributeRepository
     * @singleton integernet_solr/bridge_categoryRepository
     * @singleton integernet_solr/result
     * @singleton integernet_solr/result_collection
     * @loadFixture catalog
     */
    public function shouldShowFilteredProductsInCategory()
    {
        $this->dispatch('catalog/category/view', ['id' => self::CATEGORY_ID, '_query' => ['price' => '10-20']]);
        $this->assertResponseBodyContains('Aliens');
        $this->assertResponseBodyContains('Currently Shopping by:');
        $this->assertResponseBodyContains('1 Item(s)');
        $this->assertResponseBodyNotContains('Herbert George Wells: The War of the Worlds');
        $this->assertResponseBodyContains('Jack Williamson: The Humanoids: A Novel');
    }

    /**
     * @test
     * @registry current_category
     * @registry current_entity_key
     * @singleton catalog/layer
     * @singleton core/session
     * @singleton catalog/session
     * @singleton customer/session
     * @singleton reports/session
     * @singleton integernet_solr/bridge_attributeRepository
     * @singleton integernet_solr/bridge_categoryRepository
     * @singleton integernet_solr/result
     * @singleton integernet_solr/result_collection
     * @loadFixture catalog
     */
    public function shouldShowProductsInCategory()
    {
        $this->dispatch('catalog/category/view', ['id' => self::CATEGORY_ID]);
        $this->assertResponseBodyContains('Aliens');
        $this->assertResponseBodyContains('2 Item(s)');
        $this->assertResponseBodyContains('Herbert George Wells: The War of the Worlds');
        $this->assertResponseBodyContains('Jack Williamson: The Humanoids: A Novel');
    }
}