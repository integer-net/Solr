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

/**
 * Interface for autosuggest block, with all methods used by the autosuggest.phtml template
 *
 * @package IntegerNet\SolrSuggest\Implementor
 */
interface AutosuggestBlock
{
    /**
     * @return array
     * @todo convert to SearchTermSuggestionCollection (add more data to SearchTermSuggestion class)
     */
    public function getSearchwordSuggestions();
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
     * @return array
     * @todo convert to array of ProductSuggestion (new class) or a collection
     */
    public function getProductSuggestions();
    /**
     * @return array
     * @todo convert to array of CategorySuggestion (new class) or a collection
     */
    public function getCategorySuggestions();
    /**
     * @return array
     * @todo convert to array of AttributeSuggestion (new class) or a collection
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