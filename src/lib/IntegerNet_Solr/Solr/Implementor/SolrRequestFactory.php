<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Implementor;

use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\Solr\Request\Request;

/**
 * Interface for factory helper. Starting point for implementation
 */
interface SolrRequestFactory
{
    const REQUEST_MODE_AUTODETECT = 0;
    const REQUEST_MODE_SEARCH = 1;
    const REQUEST_MODE_SEARCHTERM_SUGGEST = 2;
    const REQUEST_MODE_AUTOSUGGEST = 3;
    const REQUEST_MODE_CATEGORY = 4;
    const REQUEST_MODE_CMS_PAGE_SUGGEST = 5;


    /**
     * Returns new configured Solr recource
     *
     * @deprecated should not be used directly from application
     * @return ResourceFacade
     */
    public function getSolrResource();

    /**
     * Returns new Solr service (search, autosuggest or category service, depending on application state or parameter)
     *
     * @param int $requestMode
     * @return Request
     */
    public function getSolrRequest($requestMode = self::REQUEST_MODE_AUTODETECT);

}