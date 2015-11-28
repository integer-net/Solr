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
     * @param int $storeId
     * @param bool $fuzzy
     * @return mixed[]
     */
    public function buildAsArray($storeId, $fuzzy);
    /**
     * Return filter query builder used to build the filter query paramter
     *
     * @return FilterQueryBuilder
     */
    public function getFilterQueryBuilder();
    /**
     * Return current page from pagination
     *
     * @return int
     */
    public function getCurrentPage();
    /**
     * Return page size from pagination
     *
     * @return int
     */
    public function getPageSize();
    /**
     * Return store id
     *
     * @return int
     */
    public function getStoreId();
}