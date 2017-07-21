<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Resource;
use IntegerNet\Solr\Config\ServerConfig;
use PHPUnit_Framework_TestCase;

class ResourceBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function withConfigMethodShouldCloneBuilder()
    {
        $original = ResourceBuilder::defaultResource();
        $cloned = $original->withConfig(new ServerConfig('host', 80, '/path', 'core0', 'core1', true, 'get', true, 'user', 'password'));
        $this->assertNotSame($original, $cloned);
        $this->assertNotEquals($original, $cloned);
        $this->assertEquals(ResourceBuilder::defaultResource(), $original);

    }
}