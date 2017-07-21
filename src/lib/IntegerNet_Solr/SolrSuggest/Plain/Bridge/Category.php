<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Bridge;

use IntegerNet\SolrSuggest\Implementor\SerializableSuggestCategory;

class Category implements SerializableSuggestCategory
{
    private $id;
    private $title;
    private $url;

    /**
     * @param $id
     * @param $title
     * @param $url
     */
    public function __construct($id, $title, $url)
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
     * Title is the name or full path, depends how it has been stored.
     * Only one of getName() or getPath() will be called
     *
     * @return string
     */
    public function getName()
    {
        return $this->title;
    }

    /**
     * Title is the name or full path, depends how it has been stored
     * Only one of getName() or getPath() will be called
     *
     * @param string $separator
     * @return string
     */
    public function getPath($separator)
    {
        return $this->title;
    }

}