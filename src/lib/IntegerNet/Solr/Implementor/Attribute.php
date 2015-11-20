<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
interface IntegerNet_Solr_Implementor_Attribute
{
    /**
     * @return string
     */
    public function getAttributeCode();

    /**
     * @return string
     */
    public function getStoreLabel();

    /**
     * @return float
     */
    public function getSolrBoost();

    /**
     * @return IntegerNet_Solr_Implementor_Source
     */
    public function getSource();

    /**
     * @return bool
     */
    public function getIsSearchable();

    /**
     * @return string
     */
    public function getBackendType();

    /**
     * @return bool
     */
    public function getUsedForSortBy();
}