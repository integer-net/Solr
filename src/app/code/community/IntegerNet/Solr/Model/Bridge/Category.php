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
     * @var string[]
     */
    protected $_categoryPathNames = array();

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param string[] $categoryPathNames
     */
    public function __construct(Mage_Catalog_Model_Category $category, array $categoryPathNames)
    {
        $this->_category = $category;
        $this->_categoryPathNames = $categoryPathNames;
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
     * @return string
     */
    public function getName()
    {
        return $this->_category->getName();
    }

    /**
     * @param string $separator
     * @return string
     */
    public function getPath($separator)
    {
        return implode($separator, $this->_categoryPathNames);
    }

}
