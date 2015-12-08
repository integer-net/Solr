<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Indexer;

/**
 * Represents a document to be indexed by Solr
 *
 * @package IntegerNet\Solr\Implementor
 */
interface IndexDocument
{
    /**
     * @return int
     */
    public function getSolrId();

    /**
     * @return bool
     */
    public function isIndexable();

    /**
     * @return array
     */
    public function getData();
}