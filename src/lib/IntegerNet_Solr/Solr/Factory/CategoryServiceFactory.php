<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Factory;

use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Query\CategoryParamsBuilder;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;
use IntegerNet\Solr\SolrResource;
use Psr\Log\LoggerInterface;

class CategoryServiceFactory extends SolrServiceFactory
{
    /**
     * @var $categoryId int
     */
    private $categoryId;

    /**
     * @param ApplicationContext $applicationContext
     * @param SolrResource $resource
     * @param FilterQueryBuilder $filterQueryBuilder
     * @param $categoryId
     */
    public function __construct(ApplicationContext $applicationContext, SolrResource $resource, FilterQueryBuilder $filterQueryBuilder, $categoryId)
    {
        parent::__construct($applicationContext, $resource, $filterQueryBuilder);
        $this->categoryId = $categoryId;
    }


    public function createParamsBuilder()
    {
        return new CategoryParamsBuilder(
            $this->getAttributeRepository(),
            $this->getFilterQueryBuilder(),
            $this->getPagination(),
            $this->getResultsConfig(),
            $this->getCategoryId()
        );
    }

    public function createSolrService()
    {
        return new \IntegerNet\Solr\CategoryService(
            $this->getCategoryId(),
            $this->getResource(),
            $this->createParamsBuilder(),
            $this->getLogger(),
            $this->getEventDispatcher()
        );
    }

    /**
     * @return int
     */
    protected function getCategoryId()
    {
        return $this->categoryId;
    }
}