<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\SolrCms\Implementor;

use IntegerNet\Solr\Indexer\IndexDocument;

/**
 */
interface PageRenderer
{
    /**
     * Render page block and add HTML to index document:
     *  - result_html_autosuggest_nonindex - Block in auto suggest (always)
     *  - result_html_list_nonindex - Block in list view (only if $useHtmlInResults is true)
     *  - result_html_grid_nonindex - Block in grid view (only if $useHtmlInResults is true)
     *
     * @param Page $page
     * @param IndexDocument $pageData
     * @param bool $useHtmlInResults
     */
    public function addResultHtmlToPageData(Page $page, IndexDocument $pageData, $useHtmlInResults);
}