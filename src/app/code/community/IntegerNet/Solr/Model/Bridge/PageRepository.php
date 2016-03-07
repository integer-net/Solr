<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\SolrCms\Implementor\PageRepository;
use IntegerNet\SolrCms\Implementor\PageIterator;

class IntegerNet_Solr_Model_Bridge_PageRepository implements PageRepository
{
    /**
     * @var int
     */
    protected $_pageSize;

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSizeForIndex($pageSize)
    {
        $this->_pageSize = $pageSize;
        return $this;
    }

    /**
     * Return page iterator, which may implement lazy loading
     *
     * @param int $storeId Pages will be returned that are visible in this store and with store specific values
     * @param null|int[] $pageIds filter by product ids
     * @return PageIterator
     */
    public function getPagesForIndex($storeId, $pageIds = null)
    {
        return new IntegerNet_Solr_Model_Bridge_LazyPageIterator($storeId, $pageIds, $this->_pageSize);
    }
}