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
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Query\CategoryParamsBuilder;
use IntegerNet\Solr\Query\CategoryQueryBuilder;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\CategoryService;

class CategoryServiceFactory extends SolrServiceFactory
{
    /**
     * @var $categoryId int
     */
    private $categoryId;

    /**
     * @param ApplicationContext $applicationContext
     * @param ResourceFacade $resource
     * @param $storeId
     * @param $categoryId
     */
    public function __construct(ApplicationContext $applicationContext, ResourceFacade $resource, $storeId, $categoryId)
    {
        $this->categoryId = $categoryId;
        parent::__construct($applicationContext, $resource, $storeId);
        $this->getFilterQueryBuilder()->setIsCategoryPage(true);
    }

    protected function createQueryBuilder()
    {
        return new CategoryQueryBuilder(
            $this->getCategoryId(),
            $this->getAttributeRepository(), $this->getPagination(),
            $this->createParamsBuilder(), $this->getStoreId(), $this->getEventDispatcher()
        );
    }

    protected function createParamsBuilder()
    {
        return new CategoryParamsBuilder(
            $this->getAttributeRepository(),
            $this->getFilterQueryBuilder(),
            $this->getPagination(),
            $this->getResultsConfig(),
            new FuzzyConfig(false, 0, 0), //TODO check if BC breaking change (category fuzzy=false)
            $this->getStoreId(),
            $this->getCategoryId()
        );
    }

    public function createSolrService()
    {
        return new CategoryService(
            $this->getResource(),
            $this->createQueryBuilder(),
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