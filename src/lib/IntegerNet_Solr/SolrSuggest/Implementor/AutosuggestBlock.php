<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Implementor;
use IntegerNet\SolrSuggest\Block\AttributeSuggestion;
use IntegerNet\SolrSuggest\Block\CategorySuggestion;
use IntegerNet\SolrSuggest\Block\ProductSuggestion;
use IntegerNet\SolrSuggest\Block\SearchTermSuggestion;

/**
 * Interface for autosuggest block, with all methods used by the autosuggest.phtml template
 *
 * @package IntegerNet\SolrSuggest\Implementor
 */
interface AutosuggestBlock
{
    /**
     * @return SearchTermSuggestion[]
     */
    public function getSearchTermSuggestions();
    /**
     * @param string $resultText
     * @param string $query
     * @return string
     */
    public function highlight($resultText, $query);
    /**
     * @return string
     */
    public function getQuery();
    /**
     * @return ProductSuggestion[]
     */
    public function getProductSuggestions();
    /**
     * @return CategorySuggestion[]
     */
    public function getCategorySuggestions();
    /**
     * @return AttributeSuggestion[]
     */
    public function getAttributeSuggestions();
    /**
     * Translation
     *
     * @return string
     */
    public function __();
    /**
     * Render template
     *
     * @return string
     */
    public function toHtml();
}