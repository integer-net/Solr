<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Entity;

use IntegerNet\Solr\Implementor\Source as SourceInterface;

/**
 * Marker interface for serializable source model, used for caching. Does not extend \Serializable
 * because standard PHP serialization is accepted too.
 */
interface SerializableSource extends SourceInterface
{

}