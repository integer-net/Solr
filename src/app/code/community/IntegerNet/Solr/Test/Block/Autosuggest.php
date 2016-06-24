<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Test_Block_Autosuggest extends  EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     * @singleton core/session
     * @singleton integernet_solr/observer
     */
    public function shouldLoadCustomHelperFromCacheWithEmptyCache()
    {
        $this->setupObserverMock(false);
        $this->setupCache();
        $this->instantiateCustomHelper();
    }
    /**
     * @test
     * @singleton core/session
     * @singleton integernet_solr/observer
     */
    public function shouldLoadCustomHelperFromCacheWithPreparedCache()
    {
        $this->setupObserverMock(true);
        $this->setupCache();
        $this->instantiateCustomHelper();
    }

    private function setupObserverMock($proxyCacheRebuild)
    {
        $observerMockBuilder = EcomDev_PHPUnit_Test_Case_Util::getGroupedClassMockBuilder($this, 'model', 'integernet_solr/observer')
            ->setMethods(['applicationCleanCache']);
        if ($proxyCacheRebuild) {
            $observerMockBuilder->enableProxyingToOriginalMethods();
        }
        $observerMock = $observerMockBuilder->getMock();
        $observerMock->expects($this->once())->method('applicationCleanCache');
        $this->replaceByMock('singleton', 'integernet_solr/observer', $observerMock);
    }

    private function setupCache()
    {
        $this->mockSession('core/session');
        Mage::app()->cleanCache();
    }

    private function instantiateCustomHelper()
    {
        $this->setCurrentStore(1);
        $block = $this->app()->getLayout()->createBlock('integernet_solr/autosuggest');
        $this->assertInstanceOf(IntegerNet_Solr_Helper_Custom::class, $block->getCustomHelper());
    }
}