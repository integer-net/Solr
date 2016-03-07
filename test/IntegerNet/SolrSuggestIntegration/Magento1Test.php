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

use IntegerNet\Solr\Implementor\Config as ConfigInterface;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrSuggest\CacheBackend\File\CacheItemPool;
use IntegerNet\SolrSuggest\Plain\Cache\PsrCache;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Log\LoggerInterface;
use \RecursiveDirectoryIterator;

class Magento1Test extends \PHPUnit_Framework_TestCase
{
    const VFS_ROOT = 'var';

    private $generateMockData = false;

    /**
     * @var vfsStreamDirectory
     */
    private $vfsRoot;
    /**
     * @var int
     */
    public $counter;

    protected function setUp()
    {
        $this->counter = 0;
    }
    protected function tearDown()
    {
        if ($this->generateMockData) {
            $this->copyFixtureToFilesystem();
        }
        $this->vfsRoot = null;
    }
    private function copyFixtureToFilesystem()
    {
        $filesystemDir = $this->getFixtureCacheDir();
        $virtualDir = $this->getFixtureCacheDir(true);
        $iterator = new \RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($virtualDir),
            \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $path) {
            $relativePath = substr($path, strlen($virtualDir));
            $filesystemPath = $filesystemDir . '/' . $relativePath;
            if ($path->isDir()) {
                if (! is_dir($filesystemPath)) {
                    mkdir($filesystemPath, 0777, true);
                }
            } else {
                copy($path, $filesystemPath);
            }
        }
    }

    /**
     * Using the empty virtual cache dir, the Magento cache writer should be triggered.
     *
     * We don't test the exact output because it depends on the data from Magento.
     *
     * @see getLoadAppCallback()
     * @test
     */
    public function testWriteCacheFromMagento()
    {
        $query = 'something';
        $storeId = 1;
        $cacheDir = $this->createVirtualCacheDir();
        $this->createCustomHelperInVirtualFilesystem();
        $this->assertFalse($this->vfsRoot->getChild('cache')->getChild($this->getRelativeCacheDir())->hasChildren(), 'Precondition: cache directory is empty');
        $response = $this->processAutosuggestRequest($query, $storeId, $cacheDir);
        $this->assertEquals(200, $response->getStatus(), 'Response status should be 200 OK');
        $this->assertContains('<ul class="searchwords">', $response->getBody(), 'Response body should contain at least search term suggestions');
        $this->assertTrue($this->vfsRoot->getChild('cache')->getChild($this->getRelativeCacheDir())->hasChildren(), 'Postcondition: cache directory is not empty');
    }
    /**
     * @test
     * @dataProvider dataSuggest
     * @param $query
     * @param $storeId
     */
    public function testSuggestWithCachedConfig($query, $storeId)
    {
        $response = $this->processAutosuggestRequest($query, $storeId, $this->getFixtureCacheDir(true));
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
        return function () {
            $root = \getenv('MAGENTO_ROOT') ?: '../../htdocs';
            if ($this->hasVirtualCacheDir()) {
                $cacheDir = vfsStream::url(self::VFS_ROOT) . '/cache';
            } elseif ($this->generateMockData) {
                $cacheDir = __DIR__ . '/fixtures';
                $this->getFixtureCacheDir(true);
            } else {
                $this->fail('App callback was not expected to be used');
            }
            require_once $root . '/app/Mage.php';
            \Mage::app();
            \Mage::getConfig()->getOptions()->setData('cache_dir', $cacheDir);
            \Mage::getConfig()->getOptions()->setData('log_dir', vfsStream::url(self::VFS_ROOT) . '/log');
            return \Mage::helper('integernet_solr/factory');
        };
    }

    /**
     * @return string
     */
    private function getFixtureCacheDir($asVirtualCopy = false)
    {
        $varDir = __DIR__ . '/fixtures';
        $solrCacheDir = $varDir . '/integernet_solr';
        if (! is_dir($solrCacheDir)) {
            mkdir($solrCacheDir, 0777, true);
        }
        if ($asVirtualCopy) {
            if (is_null($this->vfsRoot)) {
                $this->vfsRoot = vfsStream::setup(self::VFS_ROOT);
            }
            $virtualCacheDir = $this->vfsRoot->url(self::VFS_ROOT) . '/cache/integernet_solr';
            if (!is_dir($virtualCacheDir)) {
                mkdir($virtualCacheDir, 0777, true);
                vfsStream::copyFromFileSystem($solrCacheDir, $this->vfsRoot->getChild('/cache/integernet_solr'));
            }
            return $virtualCacheDir;
        }
        return $solrCacheDir;
    }

    /**
     * @return string
     */
    private function createVirtualCacheDir()
    {
        $this->vfsRoot = vfsStream::setup(self::VFS_ROOT);
        $virtualCacheDir = vfsStream::url(self::VFS_ROOT) . '/cache/' . $this->getRelativeCacheDir();
        \mkdir($virtualCacheDir, 0777, true);
        return $virtualCacheDir;
    }

    /**
     * @return bool
     */
    private function hasVirtualCacheDir()
    {
        return $this->vfsRoot !== null;
    }

    private function createCustomHelperInVirtualFilesystem()
    {
        $helperFilename = vfsStream::url(self::VFS_ROOT) . '/helper.php';
        $helperCode = <<<PHP
<?php class IntegerNet_Solr_Helper_Custom {}
PHP;
        file_put_contents($helperFilename, $helperCode);;
        include $helperFilename;
        $this->assertTrue(class_exists('IntegerNet_Solr_Helper_Custom', false), 'Custom helper loaded from vfs');
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getLogMock()
    {
        $logMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $logMock->expects($this->never())->method('error');
        return $logMock;
    }

    /**
     * @param $request
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function setupFactory($request, $cacheDir)
    {
        $cacheStorage = $this->setupCacheStorage($cacheDir);
        $factory = $this->getMockBuilder(Factory::class)
            ->setConstructorArgs([$request, $cacheStorage, $this->getLoadAppCallback()])
            ->setMethods(['getSolrResource'])
            ->getMock();
        if ($this->generateMockData) {
            $this->setupMockDataGenerator($factory);
            return $factory;
        } else {
            $this->setupResourceMock($factory);
            return $factory;
        }
    }

    /**
     * @return PsrCache
     */
    private function setupCacheStorage($cacheDir)
    {
        return new PsrCache(new CacheItemPool($cacheDir));
    }

    /**
     * @param $query
     * @param $storeId
     * @param $cacheDir
     * @return Http\AutosuggestResponse
     */
    private function processAutosuggestRequest($query, $storeId, $cacheDir)
    {
        $logMock = $this->getLogMock();
        $request = new AutosuggestRequest($query, $storeId);
        /** @var \PHPUnit_Framework_MockObject_MockObject|Factory $factory */
        $factory = $this->setupFactory($request, $cacheDir);
        $response = $factory->getAutosuggestController($logMock)->process($request);
        return $response;
    }

    /**
     * @return string
     */
    private function getRelativeCacheDir()
    {
        $virtualCacheDir = 'integernet_solr';
        return $virtualCacheDir;
    }
}


class ResourceMockDataGenerator extends ResourceFacade
{
    /** @var  Magento1Test */
    private $testCase;

    /**
     * @param ConfigInterface[] $storeConfig
     * @param Magento1Test $testCase
     */
    public function __construct(array $storeConfig = array(), Magento1Test $testCase)
    {
        $this->testCase = $testCase;
        parent::__construct($storeConfig);
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
