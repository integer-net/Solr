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

use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrSuggest\Implementor\Template;
use IntegerNet\SolrSuggest\Plain\Bridge\CategoryRepository;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Takes data from application and stores it in own cache
 *
 * @package IntegerNet\SolrSuggest\Plain\Cache
 */
class CacheWriter
{
    /**
     * @var Config[]
     */
    private $storeConfigs;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var Template
     */
    private $template;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var AttributeRepository
     */
    private $attributeRepository;
    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool;
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



    public function write()
    {
        //TODO write caches for each store
    }
}