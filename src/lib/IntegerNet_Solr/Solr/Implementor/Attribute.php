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

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
interface Attribute
{
    const BACKEND_TYPE_INT = 'int';
    const BACKEND_TYPE_DECIMAL = 'decimal';
    const BACKEND_TYPE_TEXT = 'text';
    const BACKEND_TYPE_VARCHAR = 'varchar';

    const FACET_TYPE_SELECT = 'select';
    const FACET_TYPE_MULTISELECT = 'multiselect';

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
     * @return Source
     */
    public function getSource();

    /**
     * @return string
     */
    public function getFacetType();

    /**
     * @return bool
     */
    public function getIsSearchable();

    /**
     * @return string See constants. 'decimal', 'text', 'int', or 'varchar' (default)
     */
    public function getBackendType();

    /**
     * @return bool
     */
    public function getUsedForSortBy();

}