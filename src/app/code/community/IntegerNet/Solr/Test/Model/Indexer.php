<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\SolrResource;

/**
 * @loadFixture config
 */
class IntegerNet_Solr_Test_Model_Indexer extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     * @helper integernet_solr/factory
     */
    public function shouldUseFactoryForGetResource()
    {
        $factoryMock = $this->mockHelper('integernet_solr/factory', ['getSolrResource']);
        $factoryMock->enableProxyingToOriginalMethods();
        $factoryMock->expects($this->once())->method('getSolrResource');
        $this->replaceByMock('helper', 'integernet_solr/factory', $factoryMock);
        $resource = Mage::getModel('integernet_solr/indexer')->getResource();
        $this->assertInstanceOf(SolrResource::class, $resource);
    }
}