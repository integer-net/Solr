<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Plain\Block;

use IntegerNet\SolrSuggest\Implementor\AutosuggestBlock;
use IntegerNet\SolrSuggest\Implementor\Factory;
use IntegerNet\SolrSuggest\Implementor\Template;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use IntegerNet\SolrSuggest\Util\StringHighlighter;

class Autosuggest implements AutosuggestBlock
{
    /**
     * @var Factory
     */
    private $resultFactory;
    /**
     * @var AutosuggestResult
     */
    private $result;
    /**
     * @var Template
     */
    private $template;
    /**
     * @var StringHighlighter
     */
    private $highlighter;

    /**
     * @param Factory $resultFactory
     * @param Template $template
     * @param StringHighlighter $highlighter
     */
    public function __construct(Factory $resultFactory, Template $template, StringHighlighter $highlighter)
    {
        $this->resultFactory = $resultFactory;
        $this->template = $template;
        $this->highlighter = $highlighter;
    }

    /**
     * Lazy loading the Solr result
     *
     * @return AutosuggestResult
     */
    public function getResult()
    {
        if (!$this->result) {
            $this->result = $this->resultFactory->getAutosuggestResult();
        }
        return $this->result;
    }

    /**
     * @param string $resultText
     * @param string $query
     * @return string
     */
    public function highlight($resultText, $query)
    {
        return $this->highlighter->highlight($resultText, $query);
    }

    /**
     * @todo dependency inversion
     * @return string
     */
    public function getQuery()
    {
        return $this->getResult()->getQuery();
    }

    /**
     * Replacement for original translation function
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $text = array_shift($args);
        return vsprintf($text, $args);
    }

    /**
     *
     */
    public function toHtml()
    {
        include($this->template->getFilename());
    }

    public function getCustomHelper()
    {
        //TODO find different way to inject additional functions
        throw new \BadMethodCallException('custom helper functionality not implemented in plain PHP mode');
    }
}