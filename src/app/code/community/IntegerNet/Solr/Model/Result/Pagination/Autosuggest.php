<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Model_Result_Pagination_Autosuggest implements IntegerNet_Solr_Implementor_Pagination
{
    /**
     * @var IntegerNet_Solr_Config_Autosuggest
     */
    protected $_config;

    /**
     * @param IntegerNet_Solr_Config_Autosuggest $config
     */
    public function __construct(IntegerNet_Solr_Config_Autosuggest $config)
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