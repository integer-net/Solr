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

use IntegerNet\SolrSuggest\Block\DefaultCustomHelper;
use IntegerNet\SolrSuggest\Implementor\AutosuggestBlock;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use org\bovigo\vfs\vfsStream;

/**
 * @covers IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory
 * @covers IntegerNet\SolrSuggest\Block\AbstractCustomHelper
 */
class CustomHelperFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $blockMock;
    private $resultStub;
    private $cacheMock;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        $fileContent = <<<PHP
<?php
namespace IntegerNet\SolrSuggest\Block;
class CustomHelperDummy extends AbstractCustomHelper
{
    public function customMethod() { return 42; }
}
PHP;

        $vfsRoot = vfsStream::setup('var');
        $vfsRoot->addChild(vfsStream::newFile('helper.php')->withContent($fileContent));
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        $this->resultStub = $this->getMockBuilder(AutosuggestResult::class)->disableOriginalConstructor()->getMock();
        $this->blockMock = $this->getMockForAbstractClass(AutosuggestBlock::class);
        $this->cacheMock = $this->getMockBuilder(CacheReader::class)->disableOriginalConstructor()->getMock();
    }


    /**
     * @test
     */
    public function shouldLoadAndInstantiateCustomHelper()
    {
        $className = 'IntegerNet\SolrSuggest\Block\CustomHelperDummy';

        $cacheDataPath = 'foo/bar';
        $cacheData = 'The foo is bar';
        $this->blockMock->expects($this->once())->method('getResult')->willReturn($this->resultStub);
        $this->cacheMock->expects($this->once())->method('getCustomData')->with($cacheDataPath)->willReturn($cacheData);

        $customHelperFactory = new CustomHelperFactory(vfsStream::url('var/helper.php'), $className);
        $customHelper = $customHelperFactory->getCustomHelper($this->blockMock, $this->cacheMock);
        $this->assertInstanceOf($className, $customHelper);
        $this->assertEquals(42, $customHelper->customMethod());
        $this->assertSame($this->blockMock, $customHelper->getBlock());
        $this->assertSame($this->resultStub, $customHelper->getResult());
        $this->assertEquals($cacheData, $customHelper->getCacheData($cacheDataPath));
    }

    /**
     * @test
     */
    public function shouldFallbackToDefaultHelperIfClassnameEmpty()
    {
        $cacheDataPath = 'foo/bar';
        $cacheData = 'The foo is bar';
        $this->blockMock->expects($this->once())->method('getResult')->willReturn($this->resultStub);
        $this->cacheMock->expects($this->once())->method('getCustomData')->with($cacheDataPath)->willReturn($cacheData);

        $customHelperFactory = new CustomHelperFactory(vfsStream::url('var/helper.php'), '');
        $customHelper = $customHelperFactory->getCustomHelper($this->blockMock, $this->cacheMock);
        $this->assertInstanceOf(DefaultCustomHelper::class, $customHelper);
        $this->assertSame($this->blockMock, $customHelper->getBlock());
        $this->assertSame($this->resultStub, $customHelper->getResult());
        $this->assertEquals($cacheData, $customHelper->getCacheData($cacheDataPath));
    }

    /**
     * @test
     * @expectedException \IntegerNet\Solr\Exception
     * @expectedExceptionMessageRegExp /^Custom helper NonexistentHelper not found. Included file/
     */
    public function shouldThrowExceptionIfClassNotFoundAndFileIncluded()
    {
        $customHelperFactory = new CustomHelperFactory(vfsStream::url('var/helper.php'), 'NonexistentHelper');
        $customHelperFactory->getCustomHelper($this->blockMock, $this->cacheMock);
    }
    /**
     * @test
     * @expectedException \IntegerNet\Solr\Exception
     * @expectedExceptionMessageRegExp /^Custom helper NonexistentHelper not found. Could not find file to include/
     */
    public function shouldThrowExceptionIfClassNotFoundAndFileNotIncluded()
    {
        $customHelperFactory = new CustomHelperFactory(vfsStream::url('var/nonexistent.php'), 'NonexistentHelper');
        $customHelperFactory->getCustomHelper($this->blockMock, $this->cacheMock);
    }
}