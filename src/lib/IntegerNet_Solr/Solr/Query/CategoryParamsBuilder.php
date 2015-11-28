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
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Pagination;

final class CategoryParamsBuilder extends AbstractParamsBuilder
{
    private $categoryId;

    /**
     * @param AttributeRepository $attributeRepository
     * @param FilterQueryBuilder $filterQueryBuilder
     * @param Pagination $pagination
     * @param ResultsConfig $resultsConfig
     * @param int $categoryId
     */
    public function __construct(AttributeRepository $attributeRepository, FilterQueryBuilder $filterQueryBuilder, Pagination $pagination, ResultsConfig $resultsConfig, $storeId, $categoryId)
    {
        parent::__construct($attributeRepository, $filterQueryBuilder, $pagination, $resultsConfig, $storeId);
        $this->categoryId = $categoryId;
    }

    /**
     * @return string
     */
    protected function getCurrentSortField()
    {
        $sortField = $this->pagination->getCurrentOrder();
        if ($sortField === 'position') {
            return 'category_' . $this->categoryId . '_position_i';
        }
        return parent::getCurrentSortField();
    }


}