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
use IntegerNet\Solr\Request\Request;

/**
 * Interface for factory helper
 */
interface IntegerNet_Solr_Interface_Factory
{
    const REQUEST_MODE_AUTODETECT = 0;
    const REQUEST_MODE_SEARCH = 1;
    const REQUEST_MODE_SEARCHTERM_SUGGEST = 2;
    const REQUEST_MODE_AUTOSUGGEST = 3;
    const REQUEST_MODE_CATEGORY = 4;

    /**
     * Returns new configured Solr recource
     *
     * @return ResourceFacade
     */
    public function getSolrResource();

    /**
     * Returns new Solr service (search, autosuggest or category service, depending on application state)
     *
     * @param int $requestMode
     * @return Request
     */
    public function getSolrRequest($requestMode = self::REQUEST_MODE_AUTODETECT);
}