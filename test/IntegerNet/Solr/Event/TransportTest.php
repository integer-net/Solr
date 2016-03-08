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
     * @var Transport
     */
    private $transport;

    protected function setUp()
    {
        $this->transport = new Transport(['foo_bar' => 1, 'baz' => 2]);
    }

    /**
     * @test
     */
    public function testGetterAndSetter()
    {
        $this->assertEquals(1, $this->transport->getFooBar(), 'getFooBar()');
        $this->assertEquals(2, $this->transport->getBaz(), 'getBaz()');

        $this->transport->setFooBar('a')
            ->setBaz('b')
            ->setSnake_case('c');

        $this->assertEquals('a', $this->transport->getFooBar(), 'getFooBar()');
        $this->assertEquals('b', $this->transport->getBaz(), 'getBaz()');
        $this->assertEquals('c', $this->transport->getSnakeCase(), 'getSnakeCase()');
        $this->assertEquals('c', $this->transport->getSnake_case(), 'getSnake_case()');
    }
    /**
     * @test
     */
    public function testAddData()
    {
        $result = $this->transport->addData([
            'baz' => 'new value',
            'boo' => 'far',
        ]);
        $this->assertSame($this->transport, $result, 'Fluid interface');
        $this->assertEquals(['foo_bar' => 1, 'baz' => 'new value', 'boo' => 'far'], $this->transport->getArrayCopy());
    }
}