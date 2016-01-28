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
use IntegerNet\Solr\Implementor\CategoryRepository;
use IntegerNet\Solr\Implementor\Category;
use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Implementor\Factory;
use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\Solr\Request\Request;
use IntegerNet\SolrSuggest\Implementor\SearchUrl;
use IntegerNet_Solr_Autosuggest_Template;

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
     * @var \IntegerNet\SolrSuggest\Implementor\Template
     */
    private $template;
    /**
     * @var \IntegerNet\Solr\Implementor\AttributeRepository
     */
    private $attributeRepository;
    /**
     * @var \IntegerNet\Solr\Implementor\CategoryRepository
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
     * @var $solrResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    private $solrResult = null;
    /**
     * @var int
     */
    private $storeId;

    public function __construct($storeId, GeneralConfig $generalConfig, AutosuggestConfig $autosuggestConfig,
                                HasUserQuery $userQuery, SearchUrl $searchUrl, CategoryRepository $categoryRepository,
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
        //TODO extract template functionality (only for plain PHP version)
        $this->template = new IntegerNet_Solr_Autosuggest_Template();
    }

    /**
     * @return array
     */
    public function getSearchwordSuggestions()
    {
        $maxNumberSearchwordSuggestions = $this->autosuggestConfig->getMaxNumberSearchwordSuggestions();

        if (!$maxNumberSearchwordSuggestions) {
            return array();
        }

        $solrResponse = $this->searchTermSuggestRequest->doRequest();
        $collection = new SearchTermSuggestionCollection($solrResponse, $this->userQuery);
        $query = $this->getQuery();
        $counter = 1;
        mb_internal_encoding('UTF-8');
        $title = mb_strtolower(trim($query));
        $data = array(
            array(
                'title' => $title,
                'row_class' => 'odd',
                'num_of_results' => '',
                'url' => $this->searchUrl->getUrl($query)
            )
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

            $_data = array(
                'title' => $title,
                'row_class' => $counter % 2 ? 'odd' : 'even',
                'num_of_results' => $item->getNumResults(),
                'url' => $this->searchUrl->getUrl($this->escapeHtml($item->getQueryText()))
            );

            if ($counter == 1) {
                $_data['row_class'] .= ' first';
            }

            if ($item->getQueryText() == $query) {
                array_unshift($data, $_data);
            } else {
                $data[] = $_data;
            }
        }

        if (sizeof($data)) {
            $data[max(array_keys($data))]['row_class'] .= ' last';
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getProductSuggestions()
    {
        $products = $this->getSolrResult()->response->docs;

        return $products;
    }

    public function getCategorySuggestions()
    {
        $maxNumberCategories = $this->autosuggestConfig->getMaxNumberCategorySuggestions();
        if (!$maxNumberCategories) {
            return array();
        }

        $categorySuggestions = array();
        $counter = 0;

        $categoryIds = (array)$this->getSolrResult()->facet_counts->facet_fields->category;
        $categories = $this->categoryRepository->findActiveCategoriesByIds($categoryIds);

        foreach ($categoryIds as $categoryId => $numResults) {
            if (isset($categories[$categoryId])) {
                if (++$counter > $maxNumberCategories) {
                    break;
                }

                $category = $categories[$categoryId];
                $categorySuggestions[] = array(
                    'title' => $this->escapeHtml($this->_getCategoryTitle($category)),
                    'row_class' => '',
                    'num_of_results' => $numResults,
                    'url' => $this->_getCategoryUrl($category),
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
            $optionIds = (array)$this->getSolrResult()->facet_counts->facet_fields->{$attributeCode . '_facet'};

            $maxNumberAttributeValues = intval($attributeConfig['max_number_suggestions']);
            $counter = 0;
            foreach ($optionIds as $optionId => $numResults) {
                $attributeSuggestions[$attributeCode][] = array(
                    'title' => $this->getAttribute($attributeCode)->getSource()->getOptionText($optionId),
                    'row_class' => '',
                    'option_id' => $optionId,
                    'num_of_results' => $numResults,
                    'url' => $this->searchUrl->getUrl($this->getQuery(), array($attributeCode => $optionId))
                );

                if (++$counter >= $maxNumberAttributeValues && $maxNumberAttributeValues > 0) {
                    break;
                }
            }
        }

        return $attributeSuggestions;
    }

    /**
     * @param string $attributeCode
     * @return Attribute
     */
    public function getAttribute($attributeCode)
    {
        return $this->attributeRepository->getAttributeByCode($attributeCode);
    }

    /**
     * @param string $resultText
     * @param string $query
     * @return string
     */
    public function highlight($resultText, $query)
    {
        if (strpos($resultText, '<') === false) {
            return preg_replace('/(' . trim($query) . ')/i', '<span class="highlight">$1</span>', $resultText);
        }
        return preg_replace_callback('/(' . trim($query) . ')(.*?>)/i',
            array($this, '_checkOpenTag'),
            $resultText);
    }

    /**
     * @param array $matches
     * @return string
     */
    protected function _checkOpenTag($matches)
    {
        if (strpos($matches[0], '<') === false) {
            return $matches[0];
        } else {
            return '<span class="highlight">' . $matches[1] . '</span>' . $matches[2];
        }
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
        if ($this->autosuggestConfig->isShowCompleteCategoryPath() && !empty($category->getPathIds())) {
            $categoryPathIds = $category->getPathIds();
            array_shift($categoryPathIds);
            array_shift($categoryPathIds);
            array_pop($categoryPathIds);

            $categoryPathNames = $this->categoryRepository->getCategoryNames($categoryPathIds, $this->storeId);
            $categoryPathNames[] = $category->getName();
            return implode(' > ', $categoryPathNames);
        }
        return $category->getName();
    }

    protected $_suggestData = null;

    /**
     */
    public function printHtml()
    {
        if (!$this->generalConfig->isActive()) {
            return;
        }

        include($this->template->getFilename());
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
     * Wrapper for standart strip_tags() function with extra functionality for html entities
     *
     * @param string $data
     * @param string $allowableTags
     * @param bool $escape
     * @return string
     */
    public function stripTags($data, $allowableTags = null, $escape = false)
    {
        $result = strip_tags($data, $allowableTags);
        return $escape ? $this->escapeHtml($result, $allowableTags) : $result;
    }

    public function getSolrResult()
    {
        if (is_null($this->solrResult)) {
            $this->solrResult = $this->searchRequest->doRequest();
        }
        return $this->solrResult;
    }

}