<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Implementor\Stub;

use IntegerNet\Solr\Implementor\Pagination;

class PaginationStub implements Pagination
{
    const DEFAULT_PAGESIZE = 10;
    const DEFAULT_CURRENT = 1;
    /** @var int */
    private $pageSize;
    /** @var  int */
    private $currentPage;
    /** @var  string */
    private $currentDirection;
    /** @var  string */
    private $currentOrder;

    /**
     * PaginationStub constructor.
     * @param int $pageSize
     * @param int $currentPage
     * @param string $currentDirection
     * @param string $currentOrder
     */
    public function __construct($pageSize, $currentPage, $currentDirection, $currentOrder)
    {
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;
        $this->currentDirection = $currentDirection;
        $this->currentOrder = $currentOrder;
    }

    public static function defaultPagination()
    {
        return new self(self::DEFAULT_PAGESIZE, self::DEFAULT_CURRENT, 'ASC', 'position');
    }
    public static function alternativePagination()
    {
        return new self(12, 2, 'DESC', 'attribute1');
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }

    /**
     * @return string
     */
    public function getCurrentDirection()
    {
        return $this->currentDirection;
    }

    /**
     * @param string $currentDirection
     */
    public function setCurrentDirection($currentDirection)
    {
        $this->currentDirection = $currentDirection;
    }

    /**
     * @return string
     */
    public function getCurrentOrder()
    {
        return $this->currentOrder;
    }

    /**
     * @param string $currentOrder
     */
    public function setCurrentOrder($currentOrder)
    {
        $this->currentOrder = $currentOrder;
    }

}
