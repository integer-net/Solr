<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Test_Config_Config extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * @test
     * @loadExpections
     */
    public function globalConfig()
    {
        $this->assertModuleVersionGreaterThanOrEquals($this->expected('module')->getVersion());
        $this->assertModuleCodePool($this->expected('module')->getCodePool());
    }

    /**
     * @test
     */
    public function modelConfig()
    {
        $this->assertModelAlias('integernet_solr/indexer', 'IntegerNet_Solr_Model_Indexer');
        $this->assertModelAlias('integernet_solr/resource_indexer', 'IntegerNet_Solr_Model_Resource_Indexer');
    }

    /**
     * @test
     */
    public function helperConfig()
    {
        $this->assertHelperAlias('integernet_solr', 'IntegerNet_Solr_Helper_Data');
    }

    /**
     * @test
     */
    public function translationConfig()
    {
        $this->assertConfigNodeValue('adminhtml/translate/modules/integernet_solr/files/default', 'IntegerNet_Solr.csv');
    }
}