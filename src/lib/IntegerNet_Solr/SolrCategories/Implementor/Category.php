<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\SolrCategories\Implementor;

interface Category
{
    /**
     * @return int
     */
    public function getSolrId();
    /**
     * @param int $storeId
     * @return bool
     */
    public function isIndexable($storeId);

    public function getId();

    public function getStoreId();

    public function getSolrBoost();
    
    public function getDescription();
    
    public function getName();

    public function getUrl();
}