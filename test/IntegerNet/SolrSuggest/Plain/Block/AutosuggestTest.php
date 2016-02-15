<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Block;

use IntegerNet\SolrSuggest\Implementor\CustomHelper;
use IntegerNet\SolrSuggest\Implementor\Factory\AutosuggestResultFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\CacheReaderFactory;
use IntegerNet\SolrSuggest\Implementor\TemplateRepository;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;
use IntegerNet\SolrSuggest\Util\StringHighlighter;

class AutosuggestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldLoadCustomHelperFromCache()
    {
        $customHelperStub = $this->getMockForAbstractClass(CustomHelper::class);
        $customHelperFactoryMock = $this->getMockBuilder(CustomHelperFactory::class)
            ->setMethods(['getCustomHelper'])
            ->disableOriginalConstructor()
            ->getMock();
        $cacheReaderStub = $this->getMockBuilder(CacheReader::class)
            ->setMethods(['getCustomHelperFactory'])
            ->disableOriginalConstructor()
            ->getMock();
        $cacheReaderStub->expects($this->any())->method('getCustomHelperFactory')->willReturn($customHelperFactoryMock);
        $cacheReaderFactoryStub = $this->getMockBuilder(CacheReaderFactory::class)
            ->setMethods(['getCacheReader'])
            ->disableOriginalConstructor()
            ->getMock();
        $cacheReaderFactoryStub->expects($this->any())->method('getCacheReader')->willReturn($cacheReaderStub);
        $autosuggestResultFactoryStub = $this->getMockForAbstractClass(AutosuggestResultFactory::class);
        $templateRepositoryStub = $this->getMockForAbstractClass(TemplateRepository::class);
        $highlighterStub = $this->getMockForAbstractClass(StringHighlighter::class);
        $block = new Autosuggest(1, $autosuggestResultFactoryStub, $cacheReaderFactoryStub, $templateRepositoryStub, $highlighterStub);
        $customHelperFactoryMock->expects($this->once())
            ->method('getCustomHelper')
            ->with($block, $cacheReaderStub)
            ->willReturn($customHelperStub);
        $this->assertSame($customHelperStub, $block->getCustomHelper());
    }
}