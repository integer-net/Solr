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

interface ProductRepository
{
    /**
     * Return product iterator, which may implement lazy loading
     *
     * @param int $storeId  Products will be returned that are visible in this store and with store specific values
     * @param null|int[] $productIds filter by product ids
     * @return PagedProductIterator
     */
    public function getProductsForIndex($storeId, $productIds = null);

    /**
     * Return product iterator for child products
     *
     * @param Product $parent The composite parent product. Child products will be returned that are visible in the same store and with store specific values
     * @return ProductIterator
     */
    public function getChildProducts(Product $parent);

    /**
     * Set maximum number of products to load at once during index
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSizeForIndex($pageSize);

}