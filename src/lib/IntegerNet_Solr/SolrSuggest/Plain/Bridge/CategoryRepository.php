<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Bridge;

use IntegerNet\SolrSuggest\Implementor\SerializableSuggestCategory;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;

class CategoryRepository implements SuggestCategoryRepository
{
    /**
     * @var CacheReader
     */
    private $cacheReader;
    /**
     * @var SerializableSuggestCategory[][]
     */
    private $activeCategories = array();

    /**
     * @param CacheReader $cacheReader
     */
    public function __construct(CacheReader $cacheReader)
    {
        $this->cacheReader = $cacheReader;
    }

    /**
     * @param int $storeId
     * @return SerializableSuggestCategory[]
     */
    private function findActiveCategories($storeId)
    {
        if (! isset($this->activeCategories[$storeId])) {
            $this->activeCategories[$storeId] = array();
            foreach ($this->cacheReader->getActiveCategories($storeId) as $category) {
                $this->activeCategories[$storeId][$category->getId()] = $category;
            }
        }
        return $this->activeCategories[$storeId];
    }

    /**
     * @paream int $storeId
     * @param int[] $categoryIds
     * @return Category[]
     */
    public function findActiveCategoriesByIds($storeId, $categoryIds)
    {
        $result = array();
        $allActiveCategories = $this->findActiveCategories($storeId);
        foreach ($categoryIds as $categoryId) {
            if (isset($allActiveCategories[$categoryId])) {
                $result[$categoryId] = $allActiveCategories[$categoryId];
            }
        }
        return $result;
    }

}