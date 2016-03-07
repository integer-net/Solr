<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Implementor;


use IntegerNet\SolrSuggest\Result\AutosuggestResult;

interface CustomHelper
{
    /**
     * @return AutosuggestResult
     */
    public function getResult();

    /**
     * @return AutosuggestBlock
     */
    public function getBlock();

    /**
     * @return mixed
     */
    public function getCacheData($path);
}