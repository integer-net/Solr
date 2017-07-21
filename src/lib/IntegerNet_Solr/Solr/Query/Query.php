<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;

final class Query
{
    /**
     * @var $storeId int
     */
    private $storeId;
    /**
     * @var $queryText string
     */
    private $queryText;
    /**
     * @var $offset int
     */
    private $offset;
    /**
     * @var $limit int
     */
    private $limit;
    /**
     * @var $params array
     */
    private $params;

    /**
     * @param int $storeId
     * @param string $queryText
     * @param int $offset
     * @param int $limit
     * @param array $params
     */
    public function __construct($storeId, $queryText, $offset, $limit, array $params)
    {
        $this->storeId = $storeId;
        $this->queryText = $queryText;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->params = $params;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @return string
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

}