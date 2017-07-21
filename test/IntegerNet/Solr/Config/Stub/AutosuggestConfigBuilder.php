<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Config\Stub;
use IntegerNet\Solr\Config\AutosuggestConfig;

class AutosuggestConfigBuilder
{
    const MAX_NUMBER_SEARCHWORD_SUGGESTIONS = 3;

    private $active = true;
    private $usePhpFile = 0;
    private $maxNumberSearchWordSuggestions = self::MAX_NUMBER_SEARCHWORD_SUGGESTIONS;
    private $maxNumberProductSuggestions = 5;
    private $maxNumberCategorySuggestions = 8;
    private $maxNumberCmsPageSuggestions = 0;
    private $showCompleteCategoryPath = 0;
    private $categoryLinkType = 'filter';
    private $attributeFilterSuggestions = array();

    public static function defaultConfig()
    {
        return new static;
    }

    /**
     * @param boolean $active
     * @return AutosuggestConfigBuilder
     */
    public function withActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param int $usePhpFile
     * @return AutosuggestConfigBuilder
     */
    public function withUsePhpFile($usePhpFile)
    {
        $this->usePhpFile = $usePhpFile;
        return $this;
    }

    /**
     * @param int $maxNumberSearchWordSuggesions
     * @return AutosuggestConfigBuilder
     */
    public function withMaxNumberSearchWordSuggesions($maxNumberSearchWordSuggesions)
    {
        $this->maxNumberSearchWordSuggestions = $maxNumberSearchWordSuggesions;
        return $this;
    }

    /**
     * @param int $maxNumberProductSuggesions
     * @return AutosuggestConfigBuilder
     */
    public function withMaxNumberProductSuggesions($maxNumberProductSuggesions)
    {
        $this->maxNumberProductSuggestions = $maxNumberProductSuggesions;
        return $this;
    }

    /**
     * @param int $maxNumberCategorySuggestions
     * @return AutosuggestConfigBuilder
     */
    public function withMaxNumberCategorySuggestions($maxNumberCategorySuggestions)
    {
        $this->maxNumberCategorySuggestions = $maxNumberCategorySuggestions;
        return $this;
    }

    /**
     * @param int $maxNumberCmsPageSuggestions
     * @return AutosuggestConfigBuilder
     */
    public function withMaxNumberCmsPageSuggestions($maxNumberCmsPageSuggestions)
    {
        $this->maxNumberCmsPageSuggestions = $maxNumberCmsPageSuggestions;
        return $this;
    }

    /**
     * @param int $showCompleteCategoryPath
     * @return AutosuggestConfigBuilder
     */
    public function withShowCompleteCategoryPath($showCompleteCategoryPath)
    {
        $this->showCompleteCategoryPath = $showCompleteCategoryPath;
        return $this;
    }

    /**
     * @param string $categoryLinkType
     * @return AutosuggestConfigBuilder
     */
    public function withCategoryLinkType($categoryLinkType)
    {
        $this->categoryLinkType = $categoryLinkType;
        return $this;
    }

    /**
     * @param array $attributeFilterSuggestions
     * @return AutosuggestConfigBuilder
     */
    public function withAttributeFilterSuggestions($attributeFilterSuggestions)
    {
        $this->attributeFilterSuggestions = $attributeFilterSuggestions;
        return $this;
    }



    public function build()
    {
        return new AutosuggestConfig($this->active, $this->usePhpFile, $this->maxNumberSearchWordSuggestions,
            $this->maxNumberProductSuggestions, $this->maxNumberCategorySuggestions, $this->maxNumberCmsPageSuggestions,
            $this->showCompleteCategoryPath, $this->categoryLinkType, $this->attributeFilterSuggestions);
    }
}