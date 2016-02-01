<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Plain\Cache;


use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\SolrSuggest\Implementor\SuggestAttributeRepository;
use Psr\Cache\CacheItemPoolInterface;

class AttributeCache
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool;

    /**
     * @var SuggestAttributeRepository
     */
    private $attributeRepository;

    /**
     * CategoryCache constructor.
     * @param CacheItemPoolInterface $cachePool
     * @param SuggestAttributeRepository $attributeRepository
     */
    public function __construct(CacheItemPoolInterface $cachePool, SuggestAttributeRepository $attributeRepository)
    {
        $this->cachePool = $cachePool;
        $this->attributeRepository = $attributeRepository;
    }

    public function writeAttributeCache($storeId)
    {
        $attributes = $this->attributeRepository->findFilterableInSearchAttributes($storeId);
        $attributesCacheItem = $this->cachePool->getItem("store_{$storeId}.attributes");
        $attributesCacheItem->set($attributes);
        $this->cachePool->saveDeferred($attributesCacheItem);

        $searchableAttributes = $this->attributeRepository->findSearchableAttributes($storeId);
        $searchableAttributesCacheItem = $this->cachePool->getItem("store_{$storeId}.searchable_attributes");
        $searchableAttributesCacheItem->set($searchableAttributes);
        $this->cachePool->saveDeferred($searchableAttributesCacheItem);
    }

    /**
     * @param $storeId
     * @return Attribute[]
     */
    public function getFilterableAttributes($storeId)
    {
        //TODO implement (to be used by Plain\Bridge\AttributeRepository)
    }

    /**
     * @param $storeId
     * @return Attribute[]
     */
    public function getSearchableAttributes($storeId)
    {
        //TODO implement (to be used by Plain\Bridge\AttributeRepository)
    }

}