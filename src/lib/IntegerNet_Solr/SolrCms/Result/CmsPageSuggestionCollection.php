<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

namespace IntegerNet\SolrCms\Result;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use IntegerNet\Solr\Resource\SolrResponse;
use IntegerNet\Solr\Implementor\HasUserQuery;

/**
 * Load response data into CmsPageSuggestion objects
 *
 * @package IntegerNet\SolrSuggest\Result
 */
class CmsPageSuggestionCollection implements IteratorAggregate, Countable
{
    /**
     * @var \IntegerNet\Solr\Resource\SolrResponse
     */
    private $response;
    /**
     * @var \IntegerNet\Solr\Implementor\HasUserQuery
     */
    private $userQuery;
    /**
     * @var null|ArrayIterator
     */
    private $loadedIterator;

    /**
     * @param SolrResponse $response
     * @param HasUserQuery $userQuery
     */
    public function __construct(SolrResponse $response, HasUserQuery $userQuery)
    {
        $this->response = $response;
        $this->userQuery = $userQuery;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        if ($this->loadedIterator === null) {
            $this->loadedIterator = new ArrayIterator($this->loadData());
        }
        return $this->loadedIterator;
    }

    /**
     * Load data
     *
     * @return  array
     */
    private function loadData()
    {
        return $this->getResponse()->response->docs;
    }

    public function count()
    {
        return $this->getSize();
    }

    public function getSize()
    {
        return $this->getIterator()->count();
    }

    /**
     * @return SolrResponse
     */
    private function getResponse()
    {
        return $this->response;
    }
}