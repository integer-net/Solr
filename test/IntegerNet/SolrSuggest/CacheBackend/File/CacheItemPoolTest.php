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
use IntegerNet\SolrSuggest\Plain\Cache\InvalidCacheItemValueException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class CacheItemPoolTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_ROOT = 'cache';
    private static $NUMERIC_INPUT = ['item-1' => 23, 'item-2' => 42, 'item-3' => 1337];

    /**
     * @var vfsStreamDirectory
     */
    private $vfsRoot;

    protected function setUp()
    {
        if (! $this->hasDependencies()) {
            $this->vfsRoot = vfsStream::setup(self::CACHE_ROOT);
        }
    }

    /**
     * Tast case for basic functionality
     *
     * @test
     */
    public function shouldWriteAndReadSerializedObjects()
    {
        $inputObject = (object)['someKey' => 'someValue'];
        $inputKey = 'item-1';
        $input = [$inputKey => $inputObject];

        $writeCacheItemPool = $this->setUpCacheItemPool();
        $this->assertFalse($writeCacheItemPool->hasItem($inputKey), 'has item should return false');
        $newItem = $writeCacheItemPool->getItem($inputKey);
        $this->assertInstanceOf(CacheItem::class, $newItem);
        $this->assertFalse($newItem->isHit(), 'no cache hit');
        $newItem->set($inputObject);
        $this->assertTrue($writeCacheItemPool->save($newItem), 'save successful');
        $this->assertTrue($this->vfsRoot->hasChild('item-1'));

        $readCacheItemPool = $this->setUpCacheItemPool();
        $this->assertTrue($readCacheItemPool->hasItem($inputKey), 'has item should return true');
        $loadedItem = $readCacheItemPool->getItem($inputKey);
        $this->assertInstanceOf(CacheItem::class, $loadedItem);
        $this->assertEquals($inputObject, $loadedItem->get());
        $this->assertEquals($inputKey, $loadedItem->getKey());
        $this->assertTrue($loadedItem->isHit(), 'cache hit');

        return $input;
    }

    /**
     * Our implementation does not use deferred saving
     *
     * ("A Pool object MAY delay persisting a deferred cache item")
     *
     * @test
     */
    public function shouldWriteOnSaveDeferred()
    {
        $input = self::$NUMERIC_INPUT;
        $writeCacheItemPool = $this->setUpCacheItemPool();
        foreach ($input as $inputKey => $inputData) {
            $this->assertFalse($writeCacheItemPool->hasItem($inputKey), 'has item should return false');
            $cacheItem = $writeCacheItemPool->getItem($inputKey);
            $cacheItem->set($inputData);
            $writeCacheItemPool->saveDeferred($cacheItem);
        }
        $cacheItem->set('changed the last one afterwards');
        $readCacheItemPool = $this->setUpCacheItemPool();
        foreach ($input as $inputKey => $inputData) {
            $this->assertTrue($readCacheItemPool->hasItem($inputKey), 'deferred save should have effect after commit');
            $this->assertEquals($inputData, $readCacheItemPool->getItem($inputKey)->get());
        }
        return $input;
    }

    /**
     * @test
     * @depends shouldWriteOnSaveDeferred
     */
    public function shouldReadMultipleItems($input)
    {
        $cacheItemPool = $this->setUpCacheItemPool();
        $existentAndNonexistentKeys = array_merge(array_keys($input), ['new-item']);
        $actualItems = $cacheItemPool->getItems($existentAndNonexistentKeys);
        $this->assertCount(count($existentAndNonexistentKeys), $actualItems, 'item count should equal input size');
        foreach ($existentAndNonexistentKeys as $expectedKey) {
            $this->assertArrayHasKey($expectedKey, $actualItems);
            $this->assertInstanceOf(CacheItem::class, $actualItems[$expectedKey]);
            if (array_key_exists($expectedKey, $input)) {
                $this->assertTrue($actualItems[$expectedKey]->isHit(), 'existent key => hit');
            } else {
                $this->assertFalse($actualItems[$expectedKey]->isHit(), 'nonexistent key => no hit');
            }
        }
    }

    /**
     * @test
     * @depends shouldWriteAndReadSerializedObjects
     */
    public function testClear($input)
    {
        $cacheItemPool = $this->setUpCacheItemPool();
        foreach ($input as $inputKey => $inputData) {
            $this->assertTrue($cacheItemPool->hasItem($inputKey), 'item should be present before clear');
        }
        $cacheItemPool->clear();
        foreach ($input as $inputKey => $inputData) {
            $this->assertFalse($cacheItemPool->hasItem($inputKey), 'item should be gone after clear');
        }
    }

    /**
     * @test
     * @dataProvider dataSanitizedFilenames
     */
    public function shouldSanitizeFilenames($inputKey, $expectedFilename, $forbiddenFilename = false)
    {
        $inputData = 42;

        $writeCacheItemPool = $this->setUpCacheItemPool();
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
            ['\\', '_'],
            ['item\\sub_item', 'item_sub__item'],
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
        $writeCacheItemPool = $this->setUpCacheItemPool();
        $writeCacheItemPool->getItem($inputKey);
    }
    /**
     * data provider for invalid keys
     *
     * After all slashes are replaced (see dataSanitizedFilenames()), the only problematic cache keys
     * are "." and ".."
     *
     * @return string[][]
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

    /**
     * @test
     * @dataProvider dataInvalidSerializedValues
     * @param $invalidFileContents
     */
    public function shouldThrowExceptionOnUnserializeError($invalidFileContents)
    {
        $key = 'garbage';
        \file_put_contents(vfsStream::url(self::CACHE_ROOT) . '/'. $key, $invalidFileContents);
        $writeCacheItemPool = $this->setUpCacheItemPool();
        $this->setExpectedException(InvalidCacheItemValueException::class, 'Invalid cached value for "'.$key.'"');
        $writeCacheItemPool->getItem($key);
    }

    /**
     * @test
     */
    public function shouldDeleteSingleItem()
    {
        $input = self::$NUMERIC_INPUT;
        $writeCacheItemPool = $this->setUpCacheItemPool();
        $readCacheItemPool = $this->setUpCacheItemPool();
        foreach ($input as $inputKey => $inputData) {
            $cacheItem = $writeCacheItemPool->getItem($inputKey);
            $cacheItem->set($inputData);
            $writeCacheItemPool->save($cacheItem);
        }
        $keyToDelete = key($input);
        $this->assertTrue($readCacheItemPool->hasItem($keyToDelete), 'item should exist before delete');
        $writeCacheItemPool->deleteItem($keyToDelete);
        $this->assertFalse($readCacheItemPool->hasItem($keyToDelete), 'item should not exist after delete');
    }

    /**
     * data provider
     *
     * @return string[][]
     */
    public static function dataInvalidSerializedValues()
    {
        return [
            ['garbage'],
            ['O:16:"NONEXISTENTCLASS":0:{}'],
        ];
    }

    /**
     * @return CacheItemPool
     */
    private function setUpCacheItemPool()
    {
        $writeCacheItemPool = new CacheItemPool(vfsStream::url(self::CACHE_ROOT));
        return $writeCacheItemPool;
    }
}