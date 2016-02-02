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
use IntegerNet\SolrSuggest\Plain\Cache\CustomCache;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;

abstract class AbstractCustomHelper implements CustomHelper
{
    /**
     * @var AutosuggestBlock
     */
    private $block;

    /**
     * @var CustomCache
     */
    private $customCache;

    /**
     * AbstractCustomHelper constructor.
     * @param AutosuggestBlock $block
     * @param CustomCache $customCache
     */
    public function __construct(AutosuggestBlock $block, CustomCache $customCache)
    {
        $this->block = $block;
        $this->customCache = $customCache;
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
     * @return mixed
     */
    public function getCacheData($path)
    {
        return $this->customCache->getData($path);
    }

}