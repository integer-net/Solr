<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Bridge_ProductIdChunk
{
    /** @var  int[] */
    protected $parentIds;

    /** @var  int[][] */
    protected $childrenIds;

    /**
     * @param int[] $parentIds
     * @param int[][] $childrenIds
     */
    public function __construct($parentIds, $childrenIds)
    {
        $this->parentIds = $parentIds;
        $this->childrenIds = $childrenIds;
    }
    
    /**
     * @return int[]
     */
    public function getParentIds()
    {
        return $this->parentIds;
    }

    /**
     * @return int[][]
     */
    public function getChildrenIds()
    {
        return $this->childrenIds;
    }

    /**
     * @return int[]
     */
    public function getAllIds()
    {
        $productIds = $this->parentIds;
        foreach($this->childrenIds as $parentId => $childrenIds) {
            foreach($childrenIds as $childId) {
                $productIds[] = $childId;
            } 
        }
        return $productIds;
    }
}