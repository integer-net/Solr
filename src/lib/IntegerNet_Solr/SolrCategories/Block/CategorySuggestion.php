<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

namespace IntegerNet\SolrCategories\Block;


use IntegerNet\SolrSuggest\Block\SearchTermSuggestion;

final class CategorySuggestion
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $rowClass;
    /**
     * @var int
     */
    private $numResults;
    /**
     * @var string
     */
    private $url;

    /**
     * @param string $title
     * @param string $rowClass
     * @param int $numResults
     * @param string $url
     */
    public function __construct($title, $rowClass, $numResults, $url)
    {
        $this->title = $title;
        $this->rowClass = $rowClass;
        $this->numResults = $numResults;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getRowClass()
    {
        return $this->rowClass;
    }

    /**
     * @return int
     */
    public function getNumResults()
    {
        return $this->numResults;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns new instance with modified row class
     *
     * @param $class
     * @return SearchTermSuggestion
     */
    public function appendRowClass($class)
    {
        return new self($this->title, $this->rowClass . ' ' . $class, $this->numResults, $this->url);
    }
}