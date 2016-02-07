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
     * @var Cache
     */
    private $cache;
    /**
     * @var SuggestAttributeRepository
     */
    private $attributeRepository;

    /**
     * CategoryCache constructor.
     * @param Cache $cache
     * @param SuggestAttributeRepository $attributeRepository
     */
    public function __construct(Cache $cache, SuggestAttributeRepository $attributeRepository)
    {
        $this->cache = $cache;
        $this->attributeRepository = $attributeRepository;
    }

    public function writeAttributeCache($storeId)
    {
        $attributes = $this->attributeRepository->findFilterableInSearchAttributes($storeId);
        $this->cache->save($this->getAttributesCacheKey($storeId), $attributes);

        $searchableAttributes = $this->attributeRepository->findSearchableAttributes($storeId);
        $this->cache->save($this->getSearchableAttributesCacheKey($storeId), $searchableAttributes);
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

    /**
     * @param $storeId
     * @return string
     */
    private function getAttributesCacheKey($storeId)
    {
        return "store_{$storeId}.attributes";
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getSearchableAttributesCacheKey($storeId)
    {
        return "store_{$storeId}.searchable_attributes";
    }

}