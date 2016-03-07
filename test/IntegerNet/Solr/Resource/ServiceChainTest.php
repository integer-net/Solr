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
use PHPUnit_Framework_TestCase;

class ServiceChainTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldLookForUndefinedMethodInChain()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject[] $mockServices */
        $mockServices = [
            $this->getMock(ServiceBase::class, ['newMethod1']),
            $this->getMock(ServiceBase::class, ['newMethod1', 'newMethod2']),
            $this->getMock(ServiceBase::class, ['newMethod3']),
        ];
        $mockServices[0]->expects($this->once())->method('newMethod1')->with('parameter1')->willReturn('return1');
        $mockServices[1]->expects($this->once())->method('newMethod2')->with('parameter2')->willReturn('return2');
        $mockServices[2]->expects($this->once())->method('newMethod3')->with('parameter3')->willReturn('return3');
        $base = new ServiceBase();
        foreach ($mockServices as $mockService) {
            $base->appendService($mockService);
        }
        $this->assertEquals('return1', $base->newMethod1('parameter1'));
        $this->assertEquals('return2', $base->newMethod2('parameter2'));
        $this->assertEquals('return3', $base->newMethod3('parameter3'));
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function shouldThrowExceptionIfMethodNotFoundInChain()
    {
        $base = new ServiceBase();
        $base->appendService($this->getMock(ServiceBase::class, ['newMethod']));
        $base->nonexistendMethod();
    }
}