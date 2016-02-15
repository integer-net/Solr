<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache\Item;

use IntegerNet\SolrSuggest\Plain\Block\Template as PlainTemplate;

final class TemplateCacheItem extends AbstractCacheItem
{

    /**
     * @param int $storeId
     * @param \IntegerNet\SolrSuggest\Implementor\Template $value
     */
    public function __construct($storeId, \IntegerNet\SolrSuggest\Implementor\Template $value = null)
    {
        $this->storeId = $storeId;
        $this->value = $value;
    }

    public function getKey()
    {
        return "store_{$this->storeId}.template";
    }

    public function getValueForCache()
    {
        return $this->value->getFilename();
    }

    public function withValueFromCache($value)
    {
        return parent::withValueFromCache(new PlainTemplate($value));
    }
}