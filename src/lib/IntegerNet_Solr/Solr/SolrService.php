<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr;

interface SolrService
{
    /**
     * @param int $storeId
     * @param int $pageSize
     * @return \Apache_Solr_Response
     */
    public function doRequest($storeId, $pageSize);
}