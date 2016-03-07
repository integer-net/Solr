<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;

use IntegerNet\Solr\Query\Params\FilterQueryBuilder;

/**
 * Interface to build params array and determine other parameters for Solr service
 *
 * @package IntegerNet\Solr\Query
 */
interface ParamsBuilder
{
    /**
     * Return parameters as array as expected by solr service
     *
     * @param string $attributeToReset
     * @return mixed[]
     */
    public function buildAsArray($attributeToReset = '');

    /**
     * Return store id
     *
     * @return int
     */
    public function getStoreId();
}