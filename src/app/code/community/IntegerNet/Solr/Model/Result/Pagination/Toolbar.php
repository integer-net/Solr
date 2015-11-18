<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Model_Result_Pagination_Toolbar implements IntegerNet_Solr_Implementor_Pagination
{
    /**
     * @var Mage_Catalog_Block_Product_List_Toolbar
     */
    protected $_toolbarBlock;

    /**
     * @param Varien_Object|Mage_Catalog_Block_Product_List_Toolbar $toolbarBlock
     */
    public function __construct(Varien_Object $toolbarBlock)
    {
        $this->_toolbarBlock = $toolbarBlock;
    }
    /**
     * Returns page size
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->_toolbarBlock->getLimit();
    }

    /**
     * Returns current page
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->_toolbarBlock->getCurrentPage();
    }

    /**
     * Returns sort order
     *
     * @return string {'asc', 'desc'}
     */
    public function getCurrentDirection()
    {
        return $this->_toolbarBlock->getCurrentDirection();
    }

    /**
     * Returns sort criterion (attribute)
     *
     * @return string
     */
    public function getCurrentOrder()
    {
        return $this->_toolbarBlock->getCurrentOrder();
    }

}