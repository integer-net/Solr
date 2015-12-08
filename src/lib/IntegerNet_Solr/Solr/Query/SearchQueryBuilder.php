<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;

use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;

final class SearchQueryBuilder extends AbstractQueryBuilder
{
    /**
     * @var $searchString SearchString
     */
    private $searchString;
    /**
     * @var bool
     */
    private $broaden = false;
    /**
     * @var bool
     */
    private $allowFuzzy = true;
    /**
     * @var $fuzzyConfig FuzzyConfig
     */
    private $fuzzyConfig;

    /**
     * @param FuzzyConfig $fuzzyConfig
     * @param AttributeRepository $attributeRepository
     * @param Pagination $pagination
     * @param ParamsBuilder $paramsBuilder
     * @param int $storeId
     * @param EventDispatcher $eventDispatcher
     * @todo params builder initialization?
     */
    public function __construct(SearchString $searchString, FuzzyConfig $fuzzyConfig, AttributeRepository $attributeRepository, Pagination $pagination, ParamsBuilder $paramsBuilder, $storeId, EventDispatcher $eventDispatcher)
    {
        parent::__construct($attributeRepository, $pagination, $paramsBuilder, $storeId, $eventDispatcher);
        $this->fuzzyConfig = $fuzzyConfig;
        $this->searchString = $searchString;
    }

    /**
     * @param boolean $broaden
     * @return SearchQueryBuilder
     */
    public function setBroaden($broaden)
    {
        $this->broaden = $broaden;
        return $this;
    }

    /**
     * @param boolean $allowFuzzy
     * @return SearchQueryBuilder
     */
    public function setAllowFuzzy($allowFuzzy)
    {
        $this->allowFuzzy = $allowFuzzy;
        return $this;
    }

    /**
     * @param SearchString $searchString
     * @return SearchQueryBuilder
     */
    public function setSearchString($searchString)
    {
        $this->searchString = $searchString;
        return $this;
    }
    /**
     * @return FuzzyConfig
     */
    protected function getFuzzyConfig()
    {
        return $this->fuzzyConfig;
    }

    /**
     * @return string
     */
    protected function getQueryText()
    {
        $searchString = $this->getSearchString();

        $transportObject = new Transport(array(
            'query_text' => $searchString->getRawString(),
        ));

        $this->getEventDispatcher()->dispatch('integernet_solr_update_query_text', array('transport' => $transportObject));

        $searchString = new SearchString($transportObject->getQueryText());
        $queryText = $searchString->getEscapedString();

        $isFuzzyActive = $this->getFuzzyConfig()->isActive();
        $sensitivity = $this->getFuzzyConfig()->getSensitivity();


        if ($this->allowFuzzy && $isFuzzyActive) {
            $queryText .= '~' . floatval($sensitivity);
        } else {

            $searchValue = ($this->broaden) ? explode(' ', $queryText) : $queryText;
            $queryText = '';

            $attributes = $this->getAttributeRepository()->getSearchableAttributes();
            $isFirst    = true;

            foreach ($attributes as $attribute) {
                /** @var $attribute Attribute */
                if ($attribute->getIsSearchable() == 1) {

                    $fieldName = $this->getFieldName($attribute);

                    if (strstr($fieldName, '_f') == false) {

                        $boost = '^' . floatval($attribute->getSolrBoost());

                        if ($this->broaden) {

                            foreach ($searchValue as $value) {
                                $queryText .= ($isFirst) ? '' : ' ';
                                $queryText .= $fieldName . ':"' . trim($value) . '"~100' . $boost;
                                $isFirst = false;
                            }

                        } else {
                            $queryText .= ($isFirst) ? '' : ' ';
                            $queryText .= $fieldName . ':"' . trim($searchValue) . '"~100' . $boost;
                            $isFirst = false;
                        }
                    }
                }
            }
        }
        return $queryText;
    }

    /**
     * @return SearchString
     */
    public function getSearchString()
    {
        return $this->searchString;
    }

    /**
     * @param Attribute $attribute
     * @param bool $forSorting
     * @return string
     */
    private function getFieldName(Attribute $attribute, $forSorting = false)
    {
        if ($attribute->getUsedForSortBy()) {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f';

                case 'text':
                    return $attribute->getAttributeCode() . '_t';

                default:
                    return ($forSorting) ? $attribute->getAttributeCode() . '_s' : $attribute->getAttributeCode() . '_t';
            }
        } else {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f_mv';

                case 'text':
                    return $attribute->getAttributeCode() . '_t_mv';

                default:
                    return $attribute->getAttributeCode() . '_t_mv';
            }
        }
    }
}