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
use IntegerNet\SolrSuggest\Implementor\Factory\CacheReaderFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\AutosuggestResultFactory;
use IntegerNet\SolrSuggest\Implementor\TemplateRepository;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use IntegerNet\SolrSuggest\Util\StringHighlighter;

class Autosuggest implements AutosuggestBlock
{
    /**
     * @var int
     */
    private $storeId;
    /**
     * @var AutosuggestResultFactory
     */
    private $resultFactory;
    /**
     * @var CacheReaderFactory
     */
    private $cacheReaderFactory;
    /**
     * @var AutosuggestResult
     */
    private $result;
    /**
     * @var TemplateRepository
     */
    private $templateRepository;
    /**
     * @var StringHighlighter
     */
    private $highlighter;

    /**
     * @param $storeId
     * @param AutosuggestResultFactory $resultFactory
     * @param CacheReaderFactory $cacheReaderFactory
     * @param TemplateRepository $templateRepository
     * @param StringHighlighter $highlighter
     */
    public function __construct($storeId, AutosuggestResultFactory $resultFactory, CacheReaderFactory $cacheReaderFactory,
                                TemplateRepository $templateRepository, StringHighlighter $highlighter)
    {
        $this->storeId = $storeId;
        $this->resultFactory = $resultFactory;
        $this->cacheReaderFactory = $cacheReaderFactory;
        $this->templateRepository = $templateRepository;
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
        ob_start();
        include $this->templateRepository->getTemplateByStoreId($this->storeId)->getFilename();
        return ob_get_clean();
    }

    public function getCustomHelper()
    {
        $cacheReader = $this->cacheReaderFactory->getCacheReader();
        return $cacheReader->getCustomHelperFactory($this->storeId)->getCustomHelper($this, $cacheReader);
    }
}