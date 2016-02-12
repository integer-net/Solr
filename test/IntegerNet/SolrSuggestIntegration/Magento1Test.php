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

use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrSuggest\CacheBackend\File\CacheItemPool;
use IntegerNet\SolrSuggest\Plain\Cache\CacheStorage;
use IntegerNet\SolrSuggest\Plain\Cache\PsrCache;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Log\LoggerInterface;

class Magento1Test extends \PHPUnit_Framework_TestCase
{
    const VAR_ROOT = 'var';
    private $generateMockData = false;

    /**
     * @var vfsStreamDirectory
     */
    private $vfsRoot;
    /**
     * @var CacheStorage
     */
    private $cacheStorage;
    /**
     * @var int
     */
    public $counter;

    protected function setUp()
    {
        $this->cacheStorage = new PsrCache(new CacheItemPool($this->createVirtualCacheDir()));
        $this->counter = 0;
    }
    /**
     * @test
     * @dataProvider dataSuggest
     * @param $query
     * @param $storeId
     */
    public function testSuggest($query, $storeId)
    {
        $logMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $logMock->expects($this->never())->method('error');
        $request = new AutosuggestRequest($query, $storeId);
        /** @var \PHPUnit_Framework_MockObject_MockObject|Factory $factory */
        $factory = $this->getMockBuilder(Factory::class)
            ->setConstructorArgs([$request, $this->cacheStorage, $this->getLoadAppCallback()])
            ->setMethods(['getSolrResource'])
            ->getMock();
        if ($this->generateMockData) {
            $this->setupMockDataGenerator($factory);
        } else {
            $this->setupResourceMock($factory);
        }
        $response = $factory->getAutosuggestController($logMock)->process($request);
        $this->assertEquals(200, $response->getStatus(), 'Response status should be 200 OK');
        if ($this->generateMockData) {
            file_put_contents($this->getFixtureDir() . "/out.html", $response->getBody());
        } else {
            $this->assertEquals(file_get_contents($this->getFixtureDir() . "/out.html"), $response->getBody());
        }
    }
    public static function dataSuggest()
    {
        return [
            ['bath', 1],
            ['bath', 2],
            ['bath', 3],
            ['Men', 1],
            ['Blue Bracelets', 1],
        ];
    }

    /**
     * @return \Closure
     */
    protected function getLoadAppCallback()
    {
        //TODO only use for mock generation, mock cache as well
        return function () {
            $root = \getenv('MAGENTO_ROOT') ?: '../../htdocs';
            require_once $root . '/app/Mage.php';
            \Mage::app();
            \Mage::getConfig()->getOptions()->setData('var_dir', vfsStream::url(self::VAR_ROOT));
            return \Mage::helper('integernet_solr/factory');
        };
    }

    /**
     * @return string
     */
    private function createVirtualCacheDir()
    {
        $this->vfsRoot = vfsStream::setup(self::VAR_ROOT);
        $virtualCacheDir = vfsStream::url(self::VAR_ROOT) . '/cache/integernet_solr';
        \mkdir($virtualCacheDir, 0777, true);
        return $virtualCacheDir;
    }

    /**
     * @param $factory
     */
    private function setupMockDataGenerator($factory)
    {
        $factory->expects($this->any())->method('getSolrResource')->willReturnCallback(function () use ($factory) {
            $resourceMock = new ResourceMockDataGenerator([
                1 => $factory->getCacheReader()->getConfig(1),
                2 => $factory->getCacheReader()->getConfig(2),
                3 => $factory->getCacheReader()->getConfig(3),
            ], $this);
            return $resourceMock;
        });
    }

    /**
     * @param $factory
     */
    private function setupResourceMock($factory)
    {
        $resourceMock = $this->getMockBuilder(ResourceFacade::class)
            ->setMethods(['search'])
            ->getMock();
        $resourceMock->expects($this->any())
            ->method('search')
            ->willReturnCallback(function() {
                $this->counter++;
                $expectedArgs = unserialize(file_get_contents($this->getFixtureDir() . "/args$this->counter.txt"));
                $this->assertEquals($expectedArgs, func_get_args());
                $result = unserialize(file_get_contents($this->getFixtureDir() . "/result$this->counter.txt"));
                return $result;
            });
        $factory->expects($this->any())
            ->method('getSolrResource')
            ->willReturn($resourceMock);
    }

    /**
     * @return string
     */
    public function getFixtureDir()
    {
        $dir = __DIR__ . "/fixtures/" . preg_replace('{[^\w]}', '-', $this->getName());
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }
}


class ResourceMockDataGenerator extends ResourceFacade
{
    /** @var  Magento1Test */
    private $testCase;

    /**
     * @param Config[] $storeConfig
     * @param Magento1Test $testCase
     */
    public function __construct(array $storeConfig = array(), Magento1Test $testCase)
    {
        $this->testCase = $testCase;
        parent::__construct($storeConfig); // TODO: Change the autogenerated stub
    }

    public function search($storeId, $query, $offset = 0, $limit = 10, $params = array())
    {
        $this->testCase->counter++;
        file_put_contents($this->testCase->getFixtureDir() . "/args{$this->testCase->counter}.txt", serialize(func_get_args()));
        $result = parent::search($storeId, $query, $offset, $limit, $params);
        file_put_contents($this->testCase->getFixtureDir() . "/result{$this->testCase->counter}.txt", serialize($result));
        return $result;
    }

}
