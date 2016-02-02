<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\CacheBackend\File;


use IntegerNet\SolrSuggest\CacheBackend\CacheItem;
use IntegerNet\SolrSuggest\CacheBackend\InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class CacheItemPoolTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_ROOT = 'cache';

    /**
     * @var vfsStreamDirectory
     */
    private $vfsRoot;

    protected function setUp()
    {
        $this->vfsRoot = vfsStream::setup(self::CACHE_ROOT);
    }

    /**
     * @test
     */
    public function testWriteAndReadSerializedObjects()
    {
        $inputObject = (object)['someKey' => 'someValue'];
        $inputKey = 'item-1';

        $writeCacheItemPool = new CacheItemPool(vfsStream::url(self::CACHE_ROOT));
        $this->assertFalse($writeCacheItemPool->hasItem($inputKey), 'has item should return false');
        $newItem = $writeCacheItemPool->getItem($inputKey);
        $this->assertInstanceOf(CacheItem::class, $newItem);
        $this->assertFalse($newItem->isHit(), 'no cache hit');
        $newItem->set($inputObject);
        $this->assertTrue($writeCacheItemPool->save($newItem), 'save successful');
        $this->assertTrue($this->vfsRoot->hasChild('item-1'));

        $readCacheItemPool = new CacheItemPool(vfsStream::url(self::CACHE_ROOT));
        $this->assertTrue($readCacheItemPool->hasItem($inputKey), 'has item should return true');
        $loadedItem = $readCacheItemPool->getItem($inputKey);
        $this->assertInstanceOf(CacheItem::class, $loadedItem);
        $this->assertEquals($inputObject, $loadedItem->get());
        $this->assertEquals($inputKey, $loadedItem->getKey());
        $this->assertTrue($loadedItem->isHit(), 'cache hit');
    }

    /**
     * @test
     */
    public function testSaveDeferredAndReadMultipleItems()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function testClear()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     * @dataProvider dataSanitizedFilenames
     */
    public function shouldSanitizeFilenames($inputKey, $expectedFilename, $forbiddenFilename = false)
    {
        $inputData = 42;

        $writeCacheItemPool = new CacheItemPool(vfsStream::url(self::CACHE_ROOT));
        $newItem = $writeCacheItemPool->getItem($inputKey);
        $this->assertInstanceOf(CacheItem::class, $newItem);
        $this->assertFalse($newItem->isHit(), 'no cache hit');
        $newItem->set($inputData);
        $this->assertTrue($writeCacheItemPool->save($newItem), 'save successful');
        if ($forbiddenFilename) {
            $this->assertFalse($this->vfsRoot->hasChild($forbiddenFilename), 'no subdirectory created');
        }
        $this->assertTrue($this->vfsRoot->hasChild($expectedFilename), 'item created with all slashes removed');
    }

    /**
     * data provider
     *
     * @return array
     */
    public static function dataSanitizedFilenames()
    {
        return [
            ['./item/sub_item', '._item_sub__item', 'item'],
            ['/', '_'],
            ['_', '__', '_'],
            ['dir/../item', 'dir_.._item', 'item'],
            ['../cache/item', '.._cache_item', 'item'],
            ['/etc/passwd', '_etc_passwd'],
        ];
    }

    /**
     * @test
     * @dataProvider dataInvalidKeys
     * @expectedException \IntegerNet\SolrSuggest\CacheBackend\InvalidArgumentException
     */
    public function shouldThrowExceptionOnInvalidKeys($inputKey)
    {
        $writeCacheItemPool = new CacheItemPool(vfsStream::url(self::CACHE_ROOT));
        $writeCacheItemPool->getItem($inputKey);
    }
    /**
     * data provider for invalid keys
     *
     * After all slashes are replaced (see dataSanitizedFilenames()), the only problematic cache keys
     * are "." and ".."
     *
     * @return array
     */
    public static function dataInvalidKeys()
    {
        return [
            ['.'],
            ['..'],
            ['\\.'],
            ['.\\.'],
            ['\\\\.'], // works on some file systems but we don't allow it to be sure (and because vfsStream does not like it)
        ];
    }
}