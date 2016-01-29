<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\SuggestCategoryRepository;
class IntegerNet_Solr_Autosuggest_CategoryRepository implements SuggestCategoryRepository
{
    /**
     * @param int [] $categoryIds
     * @param int $storeId
     * @return array
     */
    public function getCategoryNames($categoryIds, $storeId)
    {
        // not used
        return array();
    }

    /**
     * @param int[] $categoryIds
     * @return \IntegerNet\Solr\Implementor\Category[]
     */
    public function findActiveCategoriesByIds($categoryIds)
    {
        $categories = array();
        foreach($categoryIds as $categoryId) {
            if ($categoryData = Mage::getStoreConfig('categories/' . $categoryId)) {
                $categories[$categoryData['id']] = new IntegerNet_Solr_Autosuggest_Category(
                    $categoryData['id'], $categoryData['title'], $categoryData['url']);
            }
        }
        return $categories;
    }

}