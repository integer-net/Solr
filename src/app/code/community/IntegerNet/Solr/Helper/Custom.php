<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * Base custom helper for autosuggest block, can be rewritten to extend autosuggest block functionality
 *
 * Note that you need a different implementation for plain PHP mode {@see \IntegerNet\SolrSuggest\Block\AbstractCustomHelper}
 */
class IntegerNet_Solr_Helper_Custom implements \IntegerNet\SolrSuggest\Implementor\CustomHelper
{
    /**
     * @var AutosuggestBlock
     */
    private $block;

    /**
     * @param \IntegerNet\SolrSuggest\Implementor\AutosuggestBlock $block
     * @return $this
     */
    public function setBlock($block)
    {
        $this->block = $block;
        return $this;
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
     * Get data directly that would be added to autosuggest cache by integernet_solr_autosuggest_config observer
     * This basic implementation only works for cached configuration data, for other data please override this method.
     *
     * @todo Consider reading the cache here as well to not have parallel implementations:
     * - fetch a CustomCache instance, from CacheReader
     * - instantiate custom helper from cache
     * - delegate getCacheData()
     * - delegate custom methods with __call()
     *
     * @return mixed
     */
    public function getCacheData($path)
    {
        return Mage::getStoreConfig($path);
    }

}