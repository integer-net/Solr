<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Bridge;

use IntegerNet\SolrSuggest\Plain\Block\Template;
use IntegerNet\SolrSuggest\Plain\Cache\CacheItemNotFoundException;
use IntegerNet\SolrSuggest\Plain\Cache\CacheReader;

class TemplateRepository implements \IntegerNet\SolrSuggest\Implementor\TemplateRepository
{
    /**
     * @var CacheReader
     */
    private $cacheReader;

    /**
     * @param CacheReader $cacheReader
     */
    public function __construct(CacheReader $cacheReader)
    {
        $this->cacheReader = $cacheReader;
    }

    /**
     * @param int $storeId
     * @return Template
     * @throws CacheItemNotFoundException
     */
    public function getTemplateByStoreId($storeId)
    {
        return $this->cacheReader->getTemplate($storeId);
    }


}