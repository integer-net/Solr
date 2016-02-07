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
     * @var Cache
     */
    private $cache;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * CategoryCache constructor.
     * @param Cache $cache
     * @param SuggestCategoryRepository $categoryRepository
     */
    public function __construct(Cache $cache, SuggestCategoryRepository $categoryRepository)
    {
        $this->cache = $cache;
        $this->categoryRepository = $categoryRepository;
    }


    public function writeCategoryCache($storeId)
    {
        $categories = $this->categoryRepository->findActiveCategories($storeId);
        $this->cache->save($this->getActiveCategoriesCacheKey($storeId), $categories);
    }

    /**
     * @param $storeId
     * @return Category[]
     */
    public function getActiveCategories($storeId)
    {
        //TODO implement (to be used by Plain\Bridge\CategoryRepository)
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getActiveCategoriesCacheKey($storeId)
    {
        return "store_{$storeId}.categories";
    }
}