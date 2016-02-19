<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\CacheBackend\File;

use IntegerNet\SolrSuggest\CacheBackend\CacheItem;
use IntegerNet\SolrSuggest\Plain\Cache\InvalidCacheItemValueException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * CacheItemPool constructor.
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {
        $path = $this->getFilePath($key);
        if (\file_exists($path)) {
            $value = @unserialize(file_get_contents($path));
            if ($value === false || $value instanceof \__PHP_Incomplete_Class) {
                throw new InvalidCacheItemValueException('Invalid cached value for "' . $key . '""');
            }
            return new CacheItem(true, $key, $value);
        }
        return CacheItem::newItem($key);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = array())
    {
        $items = array();
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *    The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *  True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        $path = $this->getFilePath($key);
        return \file_exists($path);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        foreach (scandir($this->rootDir, SCANDIR_SORT_NONE) as $cacheFile) {
            $cacheFilePath = $this->rootDir . DIRECTORY_SEPARATOR . $cacheFile;
            if (! is_file($cacheFilePath)) {
                continue;
            }
            if (! unlink($cacheFilePath)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        $filename = $this->getFilePath($key);
        \unlink($filename);
        return true;
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $keys
     *   An array of keys that should be removed from the pool.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        return false; // not supported, use clear() to flush cache
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        $filename = $this->getFilePath($item->getKey());
        \file_put_contents($filename, \serialize($item->get()));
        return true;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        // deferred functionality is not used
        $this->save($item);
        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        return true;
    }

    /**
     * @param $key
     * @return string
     */
    private function getFilePath($key)
    {
        if (in_array(str_replace('\\', '', $key), array('.', '..'))) {
            throw new \IntegerNet\SolrSuggest\CacheBackend\InvalidArgumentException('Cache key not allowed');
        }

        $charMap = array(DIRECTORY_SEPARATOR => '_', '_' => '__');
        $filename = $this->rootDir . DIRECTORY_SEPARATOR . strtr($key, $charMap);
        if (!\is_dir(\dirname($filename))) {
            \mkdir(\dirname($filename), 0770);
        }
        return $filename;
    }
}