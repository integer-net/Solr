<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Block;


use IntegerNet\SolrSuggest\Implementor\AutosuggestBlock;
use IntegerNet\SolrSuggest\Implementor\CustomHelper;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;

abstract class AbstractCustomHelper implements CustomHelper
{
    /**
     * @var AutosuggestBlock
     */
    private $block;

    /**
     * @var CacheReader
     */
    private $cacheReader;

    /**
     * AbstractCustomHelper constructor.
     * @param AutosuggestBlock $block
     * @param CacheReader $cacheReader
     */
    public function __construct(AutosuggestBlock $block, CacheReader $cacheReader)
    {
        $this->block = $block;
        $this->cacheReader = $cacheReader;
    }


    /**
     * @return AutosuggestResult
     */
    public function getResult()
    {
        return $this->block->getResult();
    }

    /**
     * @return AutosuggestBlock
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @return CacheReader
     */
    public function getCacheReader()
    {
        return $this->cacheReader;
    }

    /**
     * @return mixed
     */
    public function getCacheData($path)
    {
        return $this->cacheReader->getCustomData($path);
    }

}