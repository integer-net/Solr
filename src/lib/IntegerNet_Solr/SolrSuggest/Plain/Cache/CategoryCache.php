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


use IntegerNet\Solr\Implementor\Category;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use Psr\Cache\CacheItemPoolInterface;

class CategoryCache
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * CategoryCache constructor.
     * @param CacheItemPoolInterface $cachePool
     * @param SuggestCategoryRepository $categoryRepository
     */
    public function __construct(CacheItemPoolInterface $cachePool, SuggestCategoryRepository $categoryRepository)
    {
        $this->cachePool = $cachePool;
        $this->categoryRepository = $categoryRepository;
    }


    public function writeCategoryCache($storeId)
    {
        $categories = $this->categoryRepository->findActiveCategories($storeId);
        $categoryCacheItem = $this->cachePool->getItem("store_{$storeId}.categories");
        $categoryCacheItem->set($categories);
        $this->cachePool->saveDeferred($categoryCacheItem);
    }

    /**
     * @param $storeId
     * @return Category[]
     */
    public function getActiveCategories($storeId)
    {
        //TODO implement (to be used by Plain\Bridge\CategoryRepository)
    }
}