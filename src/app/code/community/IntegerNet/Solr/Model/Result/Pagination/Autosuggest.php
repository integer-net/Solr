<?php
use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Implementor\Pagination;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * Class IntegerNet_Solr_Model_Result_Pagination_Autosuggest
 * @todo move to lib
 */
class IntegerNet_Solr_Model_Result_Pagination_Autosuggest implements Pagination
{
    /**
     * @var AutosuggestConfig
     */
    protected $_config;

    /**
     * @param AutosuggestConfig $config
     */
    public function __construct(AutosuggestConfig $config)
    {
        $this->_config = $config;
    }
    /**
     * Returns page size
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->_config->getMaxNumberProductSuggestions();
    }

    /**
     * Returns current page
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return 1;
    }

    /**
     * Returns sort order
     *
     * @return string {'asc', 'desc'}
     */
    public function getCurrentDirection()
    {
        return 'asc';
    }

    /**
     * Returns sort criterion (attribute)
     *
     * @return string
     */
    public function getCurrentOrder()
    {
        return 'position';
    }

}