<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Service\Request;

/**
 * Interface for factory helper
 */
interface IntegerNet_Solr_Interface_Factory
{
    /**
     * Returns new configured Solr recource
     *
     * @return ResourceFacade
     */
    public function getSolrResource();

    /**
     * Returns new Solr result wrapper
     *
     * @return Request
     */
    public function getSolrRequest();
}