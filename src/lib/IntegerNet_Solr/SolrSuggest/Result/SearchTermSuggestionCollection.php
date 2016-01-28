<?php
namespace IntegerNet\SolrSuggest\Result;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

class SearchTermSuggestionCollection implements IteratorAggregate, Countable
{
    /**
     * @var \IntegerNet\Solr\Resource\SolrResponse
     */
    private $response;
    /**
     * @var \IntegerNet\Solr\Implementor\HasUserQuery
     */
    private $searchTerm;
    /**
     * @var null|ArrayIterator
     */
    private $loadedIterator;

    /**
     * @param \IntegerNet\Solr\Resource\SolrResponse $response
     */
    public function __construct(\IntegerNet\Solr\Resource\SolrResponse $response, \IntegerNet\Solr\Implementor\HasUserQuery $searchTerm)
    {
        $this->response = $response;
        $this->searchTerm = $searchTerm;
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
        $items = array();
        if (!isset($this->getResponse()->facet_counts->facet_fields->text_autocomplete)
            && !isset($this->getResponse()->spellcheck->suggestions)
        ) {
            return $this;
        }

        // Facet Search
        if (isset($this->getResponse()->facet_counts->facet_fields->text_autocomplete)) {

            $suggestions = (array)$this->getResponse()->facet_counts->facet_fields->text_autocomplete;

            foreach ($suggestions as $suggestion => $numResults) {
                $items[] = new \IntegerNet\SolrSuggest\Result\SearchTermSuggestion($suggestion, $numResults);
            }


            // Spellchecker Search
        } else if (isset($this->getResponse()->spellcheck->suggestions)) {

            $spellchecker = (array)$this->getResponse()->spellcheck->suggestions;
            $queryText = $this->searchTerm->getUserQueryText();

            foreach ($spellchecker AS $word => $query) {
                $suggestions = (array)$query->suggestion;

                foreach ($suggestions as $suggestion) {
                    $items[] = new \IntegerNet\SolrSuggest\Result\SearchTermSuggestion(
                        str_replace($word, $suggestion->word, $queryText), $suggestion->freq
                    );
                }
            }
        }

        return $items;
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
     * @return \IntegerNet\Solr\Resource\SolrResponse
     */
    private function getResponse()
    {
        return $this->response;
    }
}