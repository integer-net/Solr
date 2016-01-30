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

use IntegerNet\Solr\Config\Stub\GeneralConfigBuilder;
use IntegerNet\SolrSuggest\Implementor\AutosuggestBlock;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;

class AutosuggestControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AutosuggestBlock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $blockMock;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->blockMock = $this->getMockForAbstractClass(AutosuggestBlock::class);
    }

    /**
     * @test
     */
    public function shouldBeForbiddenIfInactive()
    {
        $inactiveConfig = GeneralConfigBuilder::defaultConfig()->withActive(false)->build();
        $this->blockMock->expects($this->never())->method($this->anything());
        $controller = new AutosuggestController($inactiveConfig, $this->blockMock);
        $response = $controller->process(new AutosuggestRequest('anything', 0));
        $this->assertEquals(403, $response->getStatus(), 'Status should be 403 (Forbidden)');
        $this->assertEquals('Forbidden: Module not active', $response->getBody());
    }
    /**
     * @test
     */
    public function shouldShowErrorIfQueryEmpty()
    {
        $defaultConfig = GeneralConfigBuilder::defaultConfig()->build();
        $this->blockMock->expects($this->never())->method($this->anything());
        $controller = new AutosuggestController($defaultConfig, $this->blockMock);
        $response = $controller->process(new AutosuggestRequest('', 0));
        $this->assertEquals(400, $response->getStatus(), 'Status should be 400 (Bad Request)');
        $this->assertEquals('Bad Request: Query missing', $response->getBody());
    }
    /**
     * @test
     */
    public function shouldRenderBlockOtherwise()
    {
        $dummyResponse = 'Hello, World!';
        $defaultConfig = GeneralConfigBuilder::defaultConfig()->build();
        $this->blockMock->expects($this->once())->method('toHtml')->willReturn($dummyResponse);
        $controller = new AutosuggestController($defaultConfig, $this->blockMock);
        $response = $controller->process(new AutosuggestRequest('foo', 0));
        $this->assertEquals(200, $response->getStatus(), 'Status should be 200 (OK)');
        $this->assertEquals($dummyResponse, $response->getBody());
    }
}