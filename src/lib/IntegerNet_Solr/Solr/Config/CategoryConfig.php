<?php
namespace IntegerNet\Solr\Config;
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
final class CategoryConfig
{
    /**
     * @var bool
     */
    private $active;
    /**
     * @var int
     */
    private $filterPosition;
    /**
     * @var bool
     */
    private $indexerActive;

    /**
     * @param bool $active
     * @param int $filterPosition
     * @param bool $indexerActive
     */
    public function __construct($active, $filterPosition, $indexerActive)
    {
        $this->active = $active;
        $this->filterPosition = $filterPosition;
        $this->indexerActive = $indexerActive;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return int
     */
    public function getFilterPosition()
    {
        return $this->filterPosition;
    }

    /**
     * @return boolean
     */
    public function isIndexerActive()
    {
        return $this->indexerActive;
    }

}