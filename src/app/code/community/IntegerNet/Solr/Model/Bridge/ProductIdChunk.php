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
    protected $parentIds = array();

    /** @var  int[][] */
    protected $childrenIds = array();
    
    protected $size = 0;

    /**
     */
    public function __construct()
    {
        $this->parentIds = array();
        $this->childrenIds = array();
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
     * @return int
     */
    public function getSize()
    {
        return $this->size;
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

    /**
     * @param int $parentId
     * @param int[] $childrenIds
     */
    public function addProductIds($parentId, $childrenIds = array()) 
    {
        $this->parentIds[] = $parentId;
        if (sizeof($childrenIds)) {
            $this->childrenIds[$parentId] = $childrenIds;
        }
        $this->size += sizeof($childrenIds) + 1;
    }
}