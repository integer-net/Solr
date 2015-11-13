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
 * Test case for the low weight replacement of Magento classes in autosuggest calls
 *
 * @loadFixture config
 */
class IntegerNet_Solr_Test_Controller_Autosuggest_Magestub extends EcomDev_PHPUnit_Test_Case
{
    protected function setUp()
    {
        parent::setUp();
        IntegerNet_Solr_Autosuggest_Mage::setConfig(new IntegerNet_Solr_Autosuggest_Config(1));
    }

    /**
     * @test
     */
    public function shouldCreateSolrResourceWithFactoryHelper()
    {
        $resource = IntegerNet_Solr_Autosuggest_Mage::helper('integernet_solr/factory')->getSolrResource();
        $this->assertInstanceOf(IntegerNet_Solr_Model_Resource_Solr::class, $resource);
        $defaultStoreConfig = $resource->getStoreConfig(1);
        $this->assertInstanceOf(IntegerNet_Solr_Config_Interface::class, $defaultStoreConfig);
        $this->assertInstanceOf(IntegerNet_Solr_Config_Indexing::class, $defaultStoreConfig->getIndexingConfig());
        $this->assertInstanceOf(IntegerNet_Solr_Config_Server::class, $defaultStoreConfig->getServerConfig());
    }
}