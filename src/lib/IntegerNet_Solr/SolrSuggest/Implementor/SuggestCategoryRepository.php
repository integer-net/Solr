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

use IntegerNet\SolrSuggest\Implementor\SuggestCategory;

interface SuggestCategoryRepository
{
    /**
     * @param int $storeId
     * @param int[] $categoryIds
     * @return SuggestCategory[]
     */
    public function findActiveCategoriesByIds($storeId, $categoryIds);
}