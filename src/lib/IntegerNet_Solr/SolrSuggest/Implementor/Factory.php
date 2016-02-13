<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Implementor;

use IntegerNet\Solr\Implementor\Config;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;
use IntegerNet\SolrSuggest\Plain\Cache\CacheWriter;

interface Factory
{
    /**
     * @return \IntegerNet\SolrSuggest\Result\AutosuggestResult
     */
    public function getAutosuggestResult();

    /**
     * @return CacheWriter
     */
    public function getCacheWriter();

    /**
     * @return CacheReader
     */
    public function getCacheReader();

    /**
     * @return Config[]
     */
    public function getStoreConfig();
}