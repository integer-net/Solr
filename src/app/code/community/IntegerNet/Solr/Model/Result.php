<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
use IntegerNet\Solr\SolrService;
use IntegerNet\Solr\Query\Params\FilterQueryBuilder;

class IntegerNet_Solr_Model_Result
{
    /**
     * @var $_solrService SolrService
     */
    protected $_solrService;
    /**
     * @var $_filterQueryBuilder FilterQueryBuilder
     */
    protected $_filterQueryBuilder;
    /**
     * @var $_solrResult null|Apache_Solr_Response
     */
    protected $_solrResult = null;

    function __construct()
    {
        $this->_solrService = Mage::helper('integernet_solr/factory')->getSolrService();
        $this->_filterQueryBuilder = $this->_solrService->getFilterQueryBuilder();
    }

    /**
     * Call Solr server twice: Once without fuzzy search, once with (if configured)
     *
     * @return Apache_Solr_Response
     */
    public function getSolrResult()
    {
        if (is_null($this->_solrResult)) {
            $this->_solrResult = $this->_solrService->doRequest();
        }

        return $this->_solrResult;
    }


    /**
     * @param IntegerNet_Solr_Model_Bridge_Attribute $attribute
     * @param int $value
     */
    public function addAttributeFilter($attribute, $value)
    {
        $this->_filterQueryBuilder->addAttributeFilter($attribute, $value);
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     */
    public function addCategoryFilter($category)
    {
        $this->_filterQueryBuilder->addCategoryFilter($category->getId());
    }

    /**
     * @param int $range
     * @param int $index
     */
    public function addPriceRangeFilterByIndex($range, $index)
    {
        $this->_filterQueryBuilder->addPriceRangeFilterByConfiguration($range, $index);
    }

    /**
     * @param float $minPrice
     * @param float $maxPrice
     */
    public function addPriceRangeFilterByMinMax($minPrice, $maxPrice = null)
    {
        $this->_filterQueryBuilder->addPriceRangeFilterByMinMax($minPrice, $maxPrice);
    }

}