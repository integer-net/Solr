<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Config\IndexingConfig;
use IntegerNet\Solr\Config\ServerConfig;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\SolrResource;

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
        $this->assertInstanceOf(SolrResource::class, $resource);
        $defaultStoreConfig = $resource->getStoreConfig(1);
        $this->assertInstanceOf(Config::class, $defaultStoreConfig);
        $this->assertInstanceOf(IndexingConfig::class, $defaultStoreConfig->getIndexingConfig());
        $this->assertInstanceOf(ServerConfig::class, $defaultStoreConfig->getServerConfig());
    }
}