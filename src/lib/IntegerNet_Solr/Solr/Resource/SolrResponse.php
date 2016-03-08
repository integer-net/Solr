<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Resource;

/**
 * Interface SolrResponse
 *
 * @todo replace Apache_Solr_Response entirely with better abstraction and defined interface
 * @package IntegerNet\Solr\Resource
 */
interface SolrResponse
{
    /**
     * @param SolrResponse $other
     * @param int $pageSize
     * @return SolrResponse
     */
    public function merge(SolrResponse $other, $pageSize);
    /**
     * Returns new result with slice from item number $from until item number $from + $length
     *
     * @param $from
     * @param $length
     * @return SolrResponse
     */
    public function slice($from, $length);

}