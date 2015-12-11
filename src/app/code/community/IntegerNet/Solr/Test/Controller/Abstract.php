<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

abstract class IntegerNet_Solr_Test_Controller_Abstract extends EcomDev_PHPUnit_Test_Case_Controller
{
    protected function setUp()
    {
        parent::setUp();
        $this->app()->getStore(0)->setConfig('integernet_solr/general/install_date', time()-1);
    }

}
