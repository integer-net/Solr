<?php
namespace IntegerNet\Solr\Implementor;
use IntegerNet\Solr\Request\HasPagination;
use IntegerNet\Solr\Request\HasSortOrder;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
interface Pagination extends HasPagination, HasSortOrder
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