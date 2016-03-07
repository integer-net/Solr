<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\Solr\Implementor;
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\IndexingConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Config\ServerConfig;

/**
 * Marker interface for serializable configuration, used for caching. Does not extend \Serializable
 * because standard PHP serialization is accepted too.
 */
interface SerializableConfig extends Config
{

}