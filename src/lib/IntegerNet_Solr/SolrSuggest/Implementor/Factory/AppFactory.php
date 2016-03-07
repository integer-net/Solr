<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Implementor\Factory;

use IntegerNet\SolrSuggest\Implementor\Factory\CacheReaderFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\CacheWriterFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\ConfigFactory;
use IntegerNet\SolrSuggest\Implementor\Factory\AutosuggestResultFactory;

/**
 * An implementation of this factory needs to be returned by $loadAppConfigCallback to write cache from application
 * on the fly
 *
 * @see IntegerNet\SolrSuggest\Plain\AppConfig
 */
interface AppFactory extends ConfigFactory, CacheWriterFactory
{
}