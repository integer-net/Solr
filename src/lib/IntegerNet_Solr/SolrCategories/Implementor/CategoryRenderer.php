<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Implementor;

use IntegerNet\Solr\Indexer\IndexDocument;

/**
 */
interface CategoryRenderer
{
    /**
     * @param Category $category
     * @param IndexDocument $categoryData
     * @param bool $useHtmlInResults
     */
    public function addResultHtmlToCategoryData(Category $category, IndexDocument $categoryData, $useHtmlInResults);
}