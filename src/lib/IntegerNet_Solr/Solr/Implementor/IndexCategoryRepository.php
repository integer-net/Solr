<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Implementor;

interface IndexCategoryRepository
{
    /**
     * @param int [] $categoryIds
     * @param int $storeId
     * @return array
     */
    public function getCategoryNames($categoryIds, $storeId);

    /**
     * Get category ids of assigned categories and all parents and without excluded categories
     *
     * @param Product $product
     * @return int[]
     */
    public function getCategoryIds($product);
    /**
     * Retrieve product category identifiers
     *
     * @param Product $product
     * @return array
     */
    public function getCategoryPositions($product);

}