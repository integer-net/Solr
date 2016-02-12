<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Result;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use IntegerNet\Solr\Resource\SolrResponse;
use IntegerNet\Solr\Implementor\HasUserQuery;

/**
 * Load response data into SearchTermSuggestion objects
 *
 * @package IntegerNet\SolrSuggest\Result
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
                if (! $numResults) {
                    continue;
                }
                $items[] = new SearchTermSuggestion($suggestion, $numResults);
            }


        // Spellchecker Search
        } else if (isset($this->getResponse()->spellcheck->suggestions)) {

            $spellchecker = (array)$this->getResponse()->spellcheck->suggestions;
            $queryText = $this->userQuery->getUserQueryText();

            foreach ($spellchecker AS $word => $query) {
                $suggestions = (array)$query->suggestion;

                foreach ($suggestions as $suggestion) {
                    $items[] = new SearchTermSuggestion(
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
     * @return SolrResponse
     */
    private function getResponse()
    {
        return $this->response;
    }
}