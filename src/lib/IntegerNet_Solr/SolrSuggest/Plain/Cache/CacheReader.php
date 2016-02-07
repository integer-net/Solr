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

class CacheReader
{
    /**
     * @var AttributeCache
     */
    private $attributeCache;
    /**
     * @var CategoryCache
     */
    private $categoryCache;
    /**
     * @var ConfigCache
     */
    private $configCache;
    /**
     * @var CustomCache
     */
    private $customCache;

    /**
     * CacheReader constructor.
     * @param AttributeCache $attributeCache
     * @param CategoryCache $categoryCache
     * @param ConfigCache $configCache
     * @param CustomCache $customCache
     */
    public function __construct(AttributeCache $attributeCache, CategoryCache $categoryCache, ConfigCache $configCache, CustomCache $customCache)
    {
        $this->attributeCache = $attributeCache;
        $this->categoryCache = $categoryCache;
        $this->configCache = $configCache;
        $this->customCache = $customCache;
    }


}