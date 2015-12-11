<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Query;

use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Query\AbstractQueryBuilder;
use IntegerNet\Solr\Query\ParamsBuilder;

final class CategoryQueryBuilder extends AbstractQueryBuilder
{
    /**
     * @var $categoryId int
     */
    private $categoryId;

    /**
     * @param int $categoryId
     * @param AttributeRepository $attributeRepository
     * @param Pagination $pagination
     * @param ParamsBuilder $paramsBuilder
     * @param int $storeId
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct($categoryId, AttributeRepository $attributeRepository, Pagination $pagination, ParamsBuilder $paramsBuilder, $storeId, EventDispatcher $eventDispatcher)
    {
        parent::__construct($attributeRepository, $pagination, $paramsBuilder, $storeId, $eventDispatcher);
        $this->categoryId = $categoryId;
    }

    protected function getQueryText()
    {
        return 'category_' . $this->categoryId . '_position_i:*';
    }

    /**
     * @return CategoryParamsBuilder
     */
    public function getParamsBuilder()
    {
        return parent::getParamsBuilder();
    }


}