<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Test_Helper_Factory extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function shouldCreateSolrResourceWithStoreConfiguration()
    {
        $resource = Mage::helper('integernet_solr/factory')->getSolrResource();
        $this->assertInstanceOf(IntegerNet_Solr_Model_Resource_Solr::class, $resource);
        $storeConfigs = [
            $resource->getStoreConfig(1),   // default store view
            $resource->getStoreConfig(0),   // admin store view
            $resource->getStoreConfig(null) // admin store view
        ];
        foreach ($storeConfigs as $storeConfig) {
            $this->assertInstanceOf(IntegerNet_Solr_Implementor_Config::class, $storeConfig);
            $this->assertInstanceOf(IntegerNet_Solr_Config_Indexing::class, $storeConfig->getIndexingConfig());
            $this->assertInstanceOf(IntegerNet_Solr_Config_Server::class, $storeConfig->getServerConfig());
        }

        $this->setExpectedException(IntegerNet_Solr_Exception::class, "Store with ID -1 not found.");
        $resource->getStoreConfig(-1);
    }

    /**
     * @test
     */
    public function shouldCreateSolrResult()
    {
        $result = Mage::helper('integernet_solr/factory')->getSolrResult();
        $this->assertInstanceOf(IntegerNet_Solr_Model_Result::class, $result);
    }
}