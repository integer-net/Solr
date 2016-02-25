<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Implementor;

use IntegerNet\Solr\Indexer\IndexDocument;

/**
 */
interface ProductRenderer
{
    /**
     * Render product block and add HTML to index document:
     *  - result_html_autosuggest_nonindex - Block in auto suggest (always)
     *  - result_html_list_nonindex - Block in list view (only if $useHtmlInResults is true)
     *  - result_html_grid_nonindex - Block in grid view (only if $useHtmlInResults is true)
     *
     * @param Product $product
     * @param IndexDocument $productData
     * @param bool $useHtmlInResults
     */
    public function addResultHtmlToProductData(Product $product, IndexDocument $productData, $useHtmlInResults);
}