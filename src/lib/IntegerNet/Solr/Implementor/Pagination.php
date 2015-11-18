<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
interface IntegerNet_Solr_Implementor_Pagination
{
    /**
     * Returns page size
     *
     * @return int
     */
    public function getPageSize();

    /**
     * Returns current page
     *
     * @return int
     */
    public function getCurrentPage();

    /**
     * Returns sort order
     *
     * @return string {'asc', 'desc'}
     */
    public function getCurrentDirection();

    /**
     * Returns sort criterion (attribute)
     *
     * @return string
     */
    public function getCurrentOrder();

}