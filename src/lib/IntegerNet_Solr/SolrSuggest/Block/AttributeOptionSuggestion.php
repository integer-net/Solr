<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Block;


final class AttributeOptionSuggestion
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $title;
    /**
     * @var int
     */
    private $numResults;
    /**
     * @var string
     */
    private $url;

    /**
     * @param int $id
     * @param string $title
     * @param int $numResults
     * @param string $url
     */
    public function __construct($id, $title, $numResults, $url)
    {
        $this->id = $id;
        $this->title = $title;
        $this->numResults = $numResults;
        $this->url = $url;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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

}