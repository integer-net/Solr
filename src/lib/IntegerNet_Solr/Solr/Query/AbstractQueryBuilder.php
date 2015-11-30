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
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;

abstract class AbstractQueryBuilder implements QueryBuilder
{
    /**
     * @var $fuzzyConfig FuzzyConfig
     */
    private $fuzzyConfig;
    /**
     * @var $attributeRepository AttributeRepository
     */
    private $attributeRepository;
    /**
     * @var $pagination Pagination
     */
    private $pagination;
    /**
     * @var $paramsBuilder ParamsBuilder
     */
    private $paramsBuilder;
    /**
     * @var $storeId int
     */
    private $storeId;
    /**
     * @var $eventDispatcher EventDispatcher
     */
    private $eventDispatcher;

    /**
     * AbstractQueryBuilder constructor.
     * @param FuzzyConfig $fuzzyConfig
     * @param AttributeRepository $attributeRepository
     * @param Pagination $pagination
     * @param ParamsBuilder $paramsBuilder
     * @param int $storeId
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(FuzzyConfig $fuzzyConfig, AttributeRepository $attributeRepository, Pagination $pagination, ParamsBuilder $paramsBuilder, $storeId, EventDispatcher $eventDispatcher)
    {
        $this->fuzzyConfig = $fuzzyConfig;
        $this->attributeRepository = $attributeRepository;
        $this->pagination = $pagination;
        $this->paramsBuilder = $paramsBuilder;
        $this->storeId = $storeId;
        $this->eventDispatcher = $eventDispatcher;
    }


    public function build()
    {
        return new Query(
            $this->storeId, $this->getQueryText(),
            0, $this->pagination->getPageSize() * $this->pagination->getCurrentPage(),
            $this->paramsBuilder->buildAsArray()
        );
    }

    abstract protected function getQueryText();

    /**
     * @return FuzzyConfig
     */
    protected function getFuzzyConfig()
    {
        return $this->fuzzyConfig;
    }

    /**
     * @return AttributeRepository
     */
    protected function getAttributeRepository()
    {
        return $this->attributeRepository;
    }

    /**
     * @return Pagination
     */
    protected function getPagination()
    {
        return $this->pagination;
    }

    /**
     * @return ParamsBuilder
     */
    protected function getParamsBuilder()
    {
        return $this->paramsBuilder;
    }

    /**
     * @return int
     */
    protected function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
    
    
}