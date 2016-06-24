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
use IntegerNet\SolrCms\Result\CmsPageSuggestionCollection;
use IntegerNet\SolrCategories\Result\CategorySuggestionCollection;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\SuggestCategory;
use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Config\CategoryConfig;
use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\Solr\Request\Request;
use IntegerNet\SolrSuggest\Block\AttributeOptionSuggestion;
use IntegerNet\SolrSuggest\Block\AttributeSuggestion;
use IntegerNet\SolrSuggest\Block\ProductSuggestion;
use IntegerNet\SolrSuggest\Block\SearchTermSuggestion;
use IntegerNet\SolrCms\Block\CmsPageSuggestion;
use IntegerNet\SolrSuggest\Block\CategorySuggestion;
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
     * @var \IntegerNet\Solr\Config\CategoryConfig
     */
    private $categoryConfig;
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
     * @var Request
     */
    private $categorySuggestRequest;
    /**
     * @var Request
     */
    private $cmsPageSuggestRequest;
    /**
     * @var $searchResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    private $searchResult;
    /**
     * @var $searchTermSuggestResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    private $searchTermSuggestResult;
    /**
     * @var $categorySuggestResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    private $categorySuggestResult;
    /**
     * @var $cmsPageSuggestResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    private $cmsPageSuggestResult;
    /**
     * @var int
     */
    private $storeId;

    /**
     * AutosuggestResult constructor.
     * @param int $storeId
     * @param GeneralConfig $generalConfig
     * @param AutosuggestConfig $autosuggestConfig
     * @param CategoryConfig $categoryConfig
     * @param HasUserQuery $userQuery
     * @param SearchUrl $searchUrl
     * @param SuggestCategoryRepository $categoryRepository
     * @param AttributeRepository $attributeRepository
     * @param Request $searchRequest
     * @param Request $searchTermSuggestRequest
     * @param Request $categorySuggestRequest
     * @param Request $cmsPageSuggestRequest
     */
    public function __construct($storeId, GeneralConfig $generalConfig, AutosuggestConfig $autosuggestConfig, CategoryConfig $categoryConfig,
                                HasUserQuery $userQuery, SearchUrl $searchUrl, SuggestCategoryRepository $categoryRepository,
                                AttributeRepository $attributeRepository, Request $searchRequest,
                                Request $searchTermSuggestRequest, Request $categorySuggestRequest, Request $cmsPageSuggestRequest)
    {
        $this->storeId = $storeId;
        $this->generalConfig = $generalConfig;
        $this->autosuggestConfig = $autosuggestConfig;
        $this->categoryConfig = $categoryConfig;
        $this->userQuery = $userQuery;
        $this->searchUrl = $searchUrl;
        $this->categoryRepository = $categoryRepository;
        $this->searchRequest = $searchRequest;
        $this->searchTermSuggestRequest = $searchTermSuggestRequest;
        $this->categorySuggestRequest = $categorySuggestRequest;
        $this->cmsPageSuggestRequest = $cmsPageSuggestRequest;
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
     * @return SearchTermSuggestion[]
     */
    public function getCmsPageSuggestions()
    {
        $maxNumberCmsPageSuggestions = $this->autosuggestConfig->getMaxNumberCmsPageSuggestions();

        if (!$maxNumberCmsPageSuggestions) {
            return array();
        }

        $solrResponse = $this->getCmsPageSuggestResult();
        $collection = new CmsPageSuggestionCollection($solrResponse, $this->userQuery);
        $query = $this->getQuery();
        $counter = 0;
        mb_internal_encoding('UTF-8');
        $title = mb_strtolower(trim($query));
        /** @var CmsPageSuggestion[] $suggestions */
        $suggestions = array();

        $titles = array($title);
        foreach ($collection as $item) { /** @var \Apache_Solr_Document $item */

            if ($counter >= $maxNumberCmsPageSuggestions) {
                break;
            }

            $title = trim($this->escapeHtml($item->title_t));
            if (in_array($title, $titles)) {
                continue;
            }

            $titles[] = $title;
            $counter++;

            $_suggestion = new CmsPageSuggestion(
                $title,
                ($counter % 2 ? 'odd' : 'even') . ($counter === 1 ? ' first' : ''),
                1,
                $item->url_s_nonindex
            );

            $suggestions[] = $_suggestion;
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

        /** @var CategorySuggestion[] $categorySuggestions */
        $categorySuggestions = array();
        $counter = 0;

        if ($this->categoryConfig->isIndexerActive()) {

            $solrResponse = $this->getCategorySuggestResult();
            $collection = new CategorySuggestionCollection($solrResponse, $this->userQuery);
            $query = $this->getQuery();
            mb_internal_encoding('UTF-8');
            $title = mb_strtolower(trim($query));

            $titles = array($title);
            foreach ($collection as $item) {
                /** @var \Apache_Solr_Document $item */

                if ($counter >= $maxNumberCategories) {
                    break;
                }

                $title = trim($this->escapeHtml($item->name_t));
                if (in_array($title, $titles)) {
                    continue;
                }

                $titles[] = $title;
                $counter++;

                $_suggestion = new CategorySuggestion(
                    $title,
                    ($counter % 2 ? 'odd' : 'even') . ($counter === 1 ? ' first' : ''),
                    1,
                    $item->url_s_nonindex
                );

                $categorySuggestions[] = $_suggestion;
            }

            if (sizeof($categorySuggestions)) {
                $lastKey = max(array_keys($categorySuggestions));
                $categorySuggestions[$lastKey] = $categorySuggestions[$lastKey]->appendRowClass('last');
            }
        } else {

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
                        '',
                        $numResults,
                        $this->_getCategoryUrl($category)
                    );

                }
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
                    $optionId,
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
        return $this->attributeRepository->getAttributeByCode($attributeCode, $this->storeId);
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
     * @param SuggestCategory $category
     * @return string
     */
    protected function _getCategoryUrl(SuggestCategory $category)
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
     * @param SuggestCategory $category
     * @return string
     */
    protected function _getCategoryTitle(SuggestCategory $category)
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

    /**
     * @return \IntegerNet\Solr\Resource\SolrResponse
     */
    private function getCategorySuggestResult()
    {
        if (is_null($this->categorySuggestResult)) {
            $this->categorySuggestResult = $this->categorySuggestRequest->doRequest();
        }
        return $this->categorySuggestResult;
    }

    /**
     * @return \IntegerNet\Solr\Resource\SolrResponse
     */
    private function getCmsPageSuggestResult()
    {
        if (is_null($this->cmsPageSuggestResult)) {
            $this->cmsPageSuggestResult = $this->cmsPageSuggestRequest->doRequest();
        }
        return $this->cmsPageSuggestResult;
    }

}