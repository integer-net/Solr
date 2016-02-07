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


use IntegerNet\Solr\Implementor\Config;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\SerializableConfig;
use IntegerNet\SolrSuggest\Implementor\Template;
use IntegerNet\SolrSuggest\Plain\Bridge\Template as PlainTemplate;

class ConfigCache
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param int                $storeId       The store id
     * @param SerializableConfig $config     The store configuration for $storeId
     * @param Template           $template The (generated) template file
     */
    public function writeStoreConfig($storeId, SerializableConfig $config, Template $template)
    {
        $this->cache->save($this->getConfigCacheKey($storeId), $config);
        $this->cache->save($this->getTemplateCacheKey($storeId), $template->getFilename());
    }

    /**
     * @param $storeId
     * @return Config
     */
    public function getConfig($storeId)
    {
        return $this->cache->load($this->getConfigCacheKey($storeId));
    }

    /**
     * @param $storeId
     * @return Template
     */
    public function getTemplate($storeId)
    {
        return new PlainTemplate(
            $this->cache->load($this->getTemplateCacheKey($storeId))
        );
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getConfigCacheKey($storeId)
    {
        return "store_{$storeId}.config";
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getTemplateCacheKey($storeId)
    {
        return "store_{$storeId}.template";
    }

}