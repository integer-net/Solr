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

use IntegerNet\Solr\Config\Stub\AutosuggestConfigBuilder;
use IntegerNet\Solr\Config\ConfigContainer;
use IntegerNet\Solr\Config\Stub\FuzzyConfigBuilder;
use IntegerNet\Solr\Config\Stub\GeneralConfigBuilder;
use IntegerNet\Solr\Config\Stub\IndexingConfigBuilder;
use IntegerNet\Solr\Config\Stub\ResultConfigBuilder;
use IntegerNet\Solr\Config\Stub\ServerConfigBuilder;
use IntegerNet\Solr\Config\Stub\StoreConfigBuilder;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\SerializableConfig;
use IntegerNet\SolrSuggest\Implementor\SuggestAttributeRepository;
use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\SolrSuggest\Implementor\Template;
use IntegerNet\SolrSuggest\Plain\Block\CustomHelperFactory;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheReader
     */
    private $cacheReader;

    /**
     * @test
     */
    public function shouldReadAllCaches()
    {
        $this->markTestIncomplete();
    }
}