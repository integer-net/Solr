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

use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\Solr\Implementor\Category;
use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\Solr\Request\Request;
use IntegerNet\SolrSuggest\Block\AttributeOptionSuggestion;
use IntegerNet\SolrSuggest\Block\AttributeSuggestion;
use IntegerNet\SolrSuggest\Block\CategorySuggestion;
use IntegerNet\SolrSuggest\Block\ProductSuggestion;
use IntegerNet\SolrSuggest\Block\SearchTermSuggestion;
use IntegerNet\SolrSuggest\Implementor\SearchUrl;

class AutosuggestResult
{
    /**
     * @var \IntegerNet\Solr\Config\GeneralConfig
     */
    private $generalConfig;
    /**
     * @var \IntegerNet\Solr\Config\AutosuggestConfig
     */
    private $autosuggestConfig;
    /**
     * @var \IntegerNet\Solr\Implementor\HasUserQuery
     */
    private $userQuery;
    /**
     * @var \IntegerNet\SolrSuggest\Implementor\SearchUrl
     */
    private $searchUrl;
    /**
     * @var \IntegerNet\Solr\Implementor\AttributeRepository
     */
    private $attributeRepository;
    /**
     * @var \IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository
     */
    private $categoryRepository;
    /**
     * @var Request
     */
    private $searchRequest;
    /**
     * @var Request
     */
    private $searchTermSuggestRequest;
    /**
     * @var $searchResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    private $searchResult;
    /**
     * @var $searchTermSuggestResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    private $searchTermSuggestResult;
    /**
     * @var int
     */
    private $storeId;

    public function __construct($storeId, GeneralConfig $generalConfig, AutosuggestConfig $autosuggestConfig,
                                HasUserQuery $userQuery, SearchUrl $searchUrl, SuggestCategoryRepository $categoryRepository,
                                AttributeRepository $attributeRepository, Request $searchRequest,
                                Request $searchTermSuggestRequest)
    {
        $this->storeId = $storeId;
        $this->generalConfig = $generalConfig;
        $this->autosuggestConfig = $autosuggestConfig;
        $this->userQuery = $userQuery;
        $this->searchUrl = $searchUrl;
        $this->categoryRepository = $categoryRepository;
        $this->searchRequest = $searchRequest;
        $this->searchTermSuggestRequest = $searchTermSuggestRequest;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Lazy loading the Solr result
     *
     * @return AutosuggestResult
     */
    public function getResult()
    {
        return $this;
    }


    /**
     * @return SearchTermSuggestion[]
     */
    public function getSearchTermSuggestions()
    {
        $maxNumberSearchwordSuggestions = $this->autosuggestConfig->getMaxNumberSearchwordSuggestions();

        if (!$maxNumberSearchwordSuggestions) {
            return array();
        }

        $solrResponse = $this->getSearchTermSuggestResult();
        $collection = new SearchTermSuggestionCollection($solrResponse, $this->userQuery);
        $query = $this->getQuery();
        $counter = 1;
        mb_internal_encoding('UTF-8');
        $title = mb_strtolower(trim($query));
        /** @var SearchTermSuggestion[] $suggestions */
        $suggestions = array();
        $suggestions[]= new SearchTermSuggestion(
            $title,
            'odd',
            null,
            $this->searchUrl->getUrl($query)
        );

        $titles = array($title);
        foreach ($collection as $item) {

            if ($counter >= $maxNumberSearchwordSuggestions) {
                break;
            }

            $title = mb_strtolower(trim($this->escapeHtml($item->getQueryText())));
            if (in_array($title, $titles)) {
                continue;
            }

            $titles[] = $title;
            $counter++;

            $_suggestion = new SearchTermSuggestion(
                $title,
                ($counter % 2 ? 'odd' : 'even') . ($counter === 1 ? ' first' : ''),
                $item->getNumResults(),
                $this->searchUrl->getUrl($this->escapeHtml($item->getQueryText()))
            );

            if ($item->getQueryText() == $query) {
                array_unshift($suggestions, $_suggestion);
            } else {
                $suggestions[] = $_suggestion;
            }
        }

        if (sizeof($suggestions)) {
            $lastKey = max(array_keys($suggestions));
            $suggestions[$lastKey] = $suggestions[$lastKey]->appendRowClass('last');
        }

        return $suggestions;
    }

    /**
     * @return ProductSuggestion[]
     */
    public function getProductSuggestions()
    {
        $products = array();
        foreach ($this->getSearchRequestResult()->response->docs as $doc) {
            $products[] = new ProductSuggestion($doc->result_html_autosuggest_nonindex);
        }

        return $products;
    }

    /**
     * @return CategorySuggestion[]
     */
    public function getCategorySuggestions()
    {
        $maxNumberCategories = $this->autosuggestConfig->getMaxNumberCategorySuggestions();
        if (!$maxNumberCategories) {
            return array();
        }

        $categorySuggestions = array();
        $counter = 0;

        $categoryIds = (array)$this->getSearchRequestResult()->facet_counts->facet_fields->category;
        $categories = $this->categoryRepository->findActiveCategoriesByIds($this->storeId, $categoryIds);

        foreach ($categoryIds as $categoryId => $numResults) {
            if (isset($categories[$categoryId])) {
                if (++$counter > $maxNumberCategories) {
                    break;
                }

                $category = $categories[$categoryId];
                $categorySuggestions[] = new CategorySuggestion(
                    $this->escapeHtml($this->_getCategoryTitle($category)),
                    $numResults,
                    $this->_getCategoryUrl($category)
                );

            }
        }

        return $categorySuggestions;
    }

    /**
     * @return array
     */
    public function getAttributeSuggestions()
    {
        $attributesConfig = $this->autosuggestConfig->getAttributeFilterSuggestions();

        if (!$attributesConfig) {
            return array();
        }

        $attributesConfig = $this->_getSortedAttributesConfig($attributesConfig);
        $attributeSuggestions = array();

        foreach ($attributesConfig as $attributeConfig) {
            $attributeCode = $attributeConfig['attribute_code'];
            $optionIds = (array)$this->getSearchRequestResult()->facet_counts->facet_fields->{$attributeCode . '_facet'};

            $maxNumberAttributeValues = intval($attributeConfig['max_number_suggestions']);
            $counter = 0;
            $optionSuggestions = array();
            foreach ($optionIds as $optionId => $numResults) {
                $optionSuggestions[] = new AttributeOptionSuggestion(
                    $this->getAttribute($attributeCode)->getSource()->getOptionText($optionId),
                    $numResults,
                    $this->searchUrl->getUrl($this->getQuery(), array($attributeCode => $optionId))
                );

                if (++$counter >= $maxNumberAttributeValues && $maxNumberAttributeValues > 0) {
                    break;
                }
            }
            if (sizeof($optionSuggestions)) {
                $attributeSuggestions[] = new AttributeSuggestion(
                    $attributeCode,
                    $this->getAttribute($attributeCode)->getStoreLabel(),
                    $optionSuggestions);
            }
        }

        return $attributeSuggestions;
    }

    /**
     * @param string $attributeCode
     * @return Attribute
     */
    private function getAttribute($attributeCode)
    {
        return $this->attributeRepository->getAttributeByCode($this->storeId, $attributeCode);
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->escapeHtml($this->userQuery->getUserQueryText());
    }

    /**
     * Escape html entities
     *
     * @param   mixed $data
     * @param   array $allowedTags
     * @return  mixed
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        if (is_array($data)) {
            $result = array();
            foreach ($data as $item) {
                $result[] = $this->escapeHtml($item);
            }
        } else {
            // process single item
            if (strlen($data)) {
                if (is_array($allowedTags) and !empty($allowedTags)) {
                    $allowed = implode('|', $allowedTags);
                    $result = preg_replace('/<([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)>/si', '##$1$2$3##', $data);
                    $result = htmlspecialchars($result, ENT_COMPAT, 'UTF-8', false);
                    $result = preg_replace('/##([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)##/si', '<$1$2$3>', $result);
                } else {
                    $result = htmlspecialchars($data, ENT_COMPAT, 'UTF-8', false);
                }
            } else {
                $result = $data;
            }
        }
        return $result;
    }

    /**
     * @param array $attributesConfig
     * @return array
     */
    protected function _getSortedAttributesConfig($attributesConfig)
    {
        $newAttributesConfig = array();
        foreach ($attributesConfig as $attributeConfig) {
            $sorting = intval($attributeConfig['sorting']);
            $newAttributesConfig[$sorting][] = $attributeConfig;
        }
        ksort($newAttributesConfig);

        $attributesConfig = array();
        foreach ($newAttributesConfig as $configs) {
            foreach ($configs as $config) {
                $attributesConfig[] = $config;
            }
        }
        return $attributesConfig;
    }

    /**
     * @param Category $category
     * @return string
     */
    protected function _getCategoryUrl(Category $category)
    {
        $linkType = $this->autosuggestConfig->getCategoryLinkType();
        if ($linkType == AutosuggestConfig::CATEGORY_LINK_TYPE_FILTER) {
            return $this->searchUrl->getUrl($this->getQuery(), array('cat' => $category->getId()));
        }

        return $category->getUrl();
    }

    /**
     * Return category name or complete path, depending on what is configured
     *
     * @param Category $category
     * @return string
     */
    protected function _getCategoryTitle(Category $category)
    {
        if ($this->autosuggestConfig->isShowCompleteCategoryPath()) {
            return $category->getPath(' > ');
        }
        return $category->getName();
    }

    protected $_suggestData = null;

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
     * @return \IntegerNet\Solr\Resource\SolrResponse
     */
    private function getSearchRequestResult()
    {
        if (is_null($this->searchResult)) {
            $this->searchResult = $this->searchRequest->doRequest();
        }
        return $this->searchResult;
    }

    /**
     * @return \IntegerNet\Solr\Resource\SolrResponse
     */
    private function getSearchTermSuggestResult()
    {
        if (is_null($this->searchTermSuggestResult)) {
            $this->searchTermSuggestResult = $this->searchTermSuggestRequest->doRequest();
        }
        return $this->searchTermSuggestResult;
    }

}