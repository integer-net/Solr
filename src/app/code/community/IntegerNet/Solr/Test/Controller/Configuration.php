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
class IntegerNet_Solr_Test_Controller_Configuration extends EcomDev_PHPUnit_Test_Case_Controller
{
    /**
     * @test
     */
    public function shouldShowStatusBlock()
    {
        $this->adminSession();
        $this->dispatch('adminhtml/system_config/edit', ['section' => 'integernet_solr']);
        $this->assertRequestRoute('adminhtml/system_config/edit');
        $this->assertLayoutBlockRendered('integernet_solr_config_status');

        $expectedMessages = [
            'Solr Module is activated.',
            'Solr server configuration is complete.',
            'Connection to Solr server established successfully.',
            'Test search request issued successfully.',
            'You haven\'t entered your license key yet.'
        ];
        foreach ($expectedMessages as $message) {
            $this->assertLayoutBlockRenderedContent('integernet_solr_config_status', $this->contains($message));
        }
    }
}