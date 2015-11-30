<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Event;
use PHPUnit_Framework_TestCase;

class TransportTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testGetterAndSetter()
    {
        $transport = new Transport(['foo_bar' => 1, 'baz' => 2]);

        $this->assertEquals(1, $transport->getFooBar(), 'getFooBar()');
        $this->assertEquals(2, $transport->getBaz(), 'getBaz()');

        $transport->setFooBar('a')
            ->setBaz('b')
            ->setSnake_case('c');

        $this->assertEquals('a', $transport->getFooBar(), 'getFooBar()');
        $this->assertEquals('b', $transport->getBaz(), 'getBaz()');
        $this->assertEquals('c', $transport->getSnakeCase(), 'getSnakeCase()');
        $this->assertEquals('c', $transport->getSnake_case(), 'getSnake_case()');
    }
}