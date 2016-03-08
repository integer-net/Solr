<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\SolrCms\Implementor;

interface PageRepository
{
    /**
     * Return page iterator, which may implement lazy loading
     *
     * @param int $storeId CMS Pages will be returned that are visible in this store and with store specific values
     * @param null|int[] $pageIds filter by cmspage ids
     * @return PageIterator
     */
    public function getPagesForIndex($storeId, $pageIds = null);

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSizeForIndex($pageSize);
}