<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Implementor;

interface CategoryRepository
{
    /**
     * Return category iterator, which may implement lazy loading
     *
     * @param int $storeId CMS Categorys will be returned that are visible in this store and with store specific values
     * @param null|int[] $categoryIds filter by cmscategory ids
     * @return CategoryIterator
     */
    public function getCategoriesForIndex($storeId, $categoryIds = null);

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSizeForIndex($pageSize);
}