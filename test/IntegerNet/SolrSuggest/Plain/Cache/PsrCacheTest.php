<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class PsrCacheTest extends \PHPUnit_Framework_TestCase
{
    const A_KEY = 'any key';
    const A_VALUE = 'any data';
    /**
     * @var PsrCache
     */
    private $cache;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheItemPoolInterface
     */
    private $cachePoolMock;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->cachePoolMock = $this->getMockForAbstractClass(CacheItemPoolInterface::class);
        $this->cache = new PsrCache($this->cachePoolMock);
    }

    /**
     * @param $key
     * @param $value
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItem
     */
    private function getItemStub($key, $value = null)
    {
        $stub = $this->getMockForAbstractClass(CacheItem::class);
        $stub->expects($this->any())->method('getKey')->willReturn($key);
        $stub->expects($this->any())->method('getValueForCache')->willReturn($value);
        return $stub;
    }

    /**
     * @test
     */
    public function shouldSaveValueToCache()
    {
        $cacheItemMock = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMock->expects($this->once())
            ->method('set')
            ->with(self::A_VALUE);

        $this->cachePoolMock->expects($this->once())
            ->method('getItem')
            ->with(self::A_KEY)
            ->willReturn($cacheItemMock);
        $this->cachePoolMock->expects($this->once())
            ->method('saveDeferred')
            ->with($cacheItemMock)
            ->willReturn(true);

        $this->cache->save($this->getItemStub(self::A_KEY, self::A_VALUE));
    }

    /**
     * @test
     */
    public function shouldOverwriteInvalidValue()
    {
        $cacheItemMock = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMock->expects($this->once())
            ->method('set')
            ->with(self::A_VALUE);

        $this->cachePoolMock->expects($this->at(0))
            ->method('getItem')
            ->with(self::A_KEY)
            ->willThrowException(new InvalidCacheItemValueException());
        $this->cachePoolMock->expects($this->at(1))
            ->method('deleteItem')
            ->willReturn(true);
        $this->cachePoolMock->expects($this->at(2))
            ->method('getItem')
            ->with(self::A_KEY)
            ->willReturn($cacheItemMock);
        $this->cachePoolMock->expects($this->once())
            ->method('saveDeferred')
            ->with($cacheItemMock)
            ->willReturn(true);

        $this->cache->save($this->getItemStub(self::A_KEY, self::A_VALUE));
    }

    /**
     * @test
     */
    public function shouldReturnCachedValue()
    {
        $cacheItemMock = $this->getMockForAbstractClass(CacheItemInterface::class);
        $cacheItemMock->expects($this->once())
            ->method('get')
            ->willReturn(self::A_VALUE);

        $this->mockRead(self::A_KEY, true, $cacheItemMock);

        $this->cache->load($this->getItemStub(self::A_KEY));
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfItemNotFound()
    {
        $this->mockRead(self::A_KEY, false);

        $this->setExpectedException(CacheItemNotFoundException::class, "Cache item " . self::A_KEY . " not found");
        $this->cache->load($this->getItemStub(self::A_KEY));

    }
    /**
     * @param $cacheKey
     * @param $isCached
     */
    private function mockRead($cacheKey, $isCached, $returnedCacheItem = null)
    {
        $this->cachePoolMock->expects($this->once())
            ->method('hasItem')
            ->with($cacheKey)
            ->willReturn($isCached);
        if ($returnedCacheItem) {
            $this->cachePoolMock->expects($this->once())
                ->method('getItem')
                ->with($cacheKey)
                ->willReturn($returnedCacheItem);
        }
    }
}