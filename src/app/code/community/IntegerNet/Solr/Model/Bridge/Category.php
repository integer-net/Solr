<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrCategories
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Category;

class IntegerNet_Solr_Model_Bridge_Category implements Category
{
    /**
     * @var Mage_Catalog_Model_Category
     */
    protected $_category;

    /**
     * @param Mage_Catalog_Model_Category $category
     */
    public function __construct(Mage_Catalog_Model_Category $category)
    {
        $this->_category = $category;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_category->getId();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_category->getUrl();
    }

    /**
     * @return int[]
     */
    public function getPathIds()
    {
        return $this->_category->getPathIds();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_category->getName();
    }

}
