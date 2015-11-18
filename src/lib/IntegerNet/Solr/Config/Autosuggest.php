<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class IntegerNet_Solr_Config_Autosuggest
{
    const USE_MAGENTO_CONTROLLER = 0;
    const USE_PHP_FILE_WITHOUT_MAGENTO = 1;
    const USE_PHP_FILE_WITH_MAGENTO = 2;

    const CATEGORY_LINK_FILTER = 'selected';
    const CATEGORY_LINK_DIRECT = 'direct';
    /**
     * @var bool
     */
    private $active;
    /**
     * @var int
     */
    private $usePhpFile;
    /**
     * @var int
     */
    private $maxNumberSearchwordSuggestions;
    /**
     * @var int
     */
    private $maxNumberProductSuggestions;
    /**
     * @var int
     */
    private $maxNumberCategorySuggestions;
    /**
     * @var bool
     */
    private $showCompleteCategoryPath;
    /**
     * @var string
     */
    private $categoryLinkType;
    /**
     * @var mixed[][]
     */
    private $attributeFilterSuggestions;

    /**
     * @param bool $active
     * @param int $usePhpFile
     * @param int $maxNumberSearchwordSuggestions
     * @param int $maxNumberProductSuggestions
     * @param int $maxNumberCategorySuggestions
     * @param bool $showCompleteCategoryPath
     * @param string $categoryLinkType
     */
    public function __construct($active, $usePhpFile, $maxNumberSearchwordSuggestions, $maxNumberProductSuggestions,
                                $maxNumberCategorySuggestions, $showCompleteCategoryPath, $categoryLinkType, $attributeFilterSuggestions)
    {
        $this->active = $active;
        $this->usePhpFile = $usePhpFile;
        $this->maxNumberSearchwordSuggestions = (int) $maxNumberSearchwordSuggestions;
        $this->maxNumberProductSuggestions = (int) $maxNumberProductSuggestions;
        $this->maxNumberCategorySuggestions = (int) $maxNumberCategorySuggestions;
        $this->showCompleteCategoryPath = $showCompleteCategoryPath;
        $this->categoryLinkType = $categoryLinkType;
        $this->attributeFilterSuggestions = $attributeFilterSuggestions;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return int
     */
    public function getUsePhpFile()
    {
        return $this->usePhpFile;
    }

    /**
     * @return int
     */
    public function getMaxNumberSearchwordSuggestions()
    {
        return $this->maxNumberSearchwordSuggestions;
    }

    /**
     * @return int
     */
    public function getMaxNumberProductSuggestions()
    {
        return $this->maxNumberProductSuggestions;
    }

    /**
     * @return int
     */
    public function getMaxNumberCategorySuggestions()
    {
        return $this->maxNumberCategorySuggestions;
    }

    /**
     * @return boolean
     */
    public function isShowCompleteCategoryPath()
    {
        return $this->showCompleteCategoryPath;
    }

    /**
     * @return string
     */
    public function getCategoryLinkType()
    {
        return $this->categoryLinkType;
    }

    /**
     * @return mixed[][]
     */
    public function getAttributeFilterSuggestions()
    {
        return $this->attributeFilterSuggestions;
    }
}