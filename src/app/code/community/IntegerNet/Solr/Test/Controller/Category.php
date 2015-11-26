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
class IntegerNet_Solr_Test_Controller_Category extends EcomDev_PHPUnit_Test_Case_Controller
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
     * @singleton integernet_solr/result_collection
     * @loadFixture catalog
     */
    public function shouldShowProductsInCategory()
    {
        $this->markTestSkipped('Still global state somewhere. only one of the test cases can run successfully');
        $this->dispatch('catalog/category/view', ['id' => self::CATEGORY_ID]);
        $this->assertResponseBodyContains('Aliens');
        $this->assertResponseBodyContains('2 Item(s)');
        $this->assertResponseBodyContains('Herbert George Wells: The War of the Worlds');
        $this->assertResponseBodyContains('Jack Williamson: The Humanoids: A Novel');
    }
}