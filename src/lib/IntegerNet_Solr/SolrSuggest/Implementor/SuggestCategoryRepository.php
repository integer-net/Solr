<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Implementor;

use IntegerNet\Solr\Implementor\Category;

interface SuggestCategoryRepository
{
    /**
     * @param int[] $categoryIds
     * @param int $storeId
     * @return array
     */
    public function getCategoryNames($categoryIds, $storeId);

    /**
     * @param int[] $categoryIds
     * @return Category[]
     */
    public function findActiveCategoriesByIds($categoryIds);

    /**
     * @param int $storeId
     * @return SerializableCategory[]
     */
    public function findActiveCategories($storeId);
}