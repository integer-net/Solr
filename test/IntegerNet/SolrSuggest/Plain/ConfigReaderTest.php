<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain;

//TODO implement config reader and writer
class ConfigReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigReadFallback()
    {
        $this->markTestIncomplete('This is just a draft for the plain client config load workflow');

        $factory = new \IntegerNet\SolrSuggest\Plain\Factory();
        try {
            $factory->getStoreConfig(0);
        } catch (ConfigReadException $e) {
            //TODO inject config implementation (Magento) to config writer
            // (will include category info, template path etc.)
            $factory->getConfigWriter()->writeConfig();
            $factory->getStoreConfig(0);
        }

    }
}