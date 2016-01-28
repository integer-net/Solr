<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Category;
class IntegerNet_Solr_Autosuggest_Category implements Category
{
    /**
     * @var array
     */
    private $id;
    private $title;
    private $url;

    /**
     * IntegerNet_Solr_Autosuggets_Category constructor.
     * @param array $id
     * @param $title
     * @param $url
     */
    public function __construct(array $id, $title, $url)
    {
        $this->id = $id;
        $this->title = $title;
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int[]
     */
    public function getPathIds()
    {
        return array();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->title;
    }

}