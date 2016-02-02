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
use IntegerNet\SolrSuggest\Block\AbstractCustomHelper;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;

/**
 * Interface for autosuggest block, with all methods used by the autosuggest.phtml template
 *
 * @package IntegerNet\SolrSuggest\Implementor
 */
interface AutosuggestBlock
{
    /**
     * Lazy loading the Solr result
     *
     * @return AutosuggestResult
     */
    public function getResult();
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

    /**
     * Returns custom helper, used to extend the autosuggest block
     *
     * @return AbstractCustomHelper
     */
    public function getCustomHelper();
}