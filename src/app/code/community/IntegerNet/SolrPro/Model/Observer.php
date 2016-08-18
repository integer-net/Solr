<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrPro
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Model_Observer
{
    /**
     * Is Enabled timage category image cache
     *
     * @var bool
     */
    protected $_isEnabled;
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_isEnabled = Mage::app()->useCache('timage');
    }
    /**
     * Check if full page cache is enabled
     *
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->_isEnabled;
    }
    /**
     * Clean full category image cache in response to catalog (product) image cache clean
     *
     * @param $observer
     */
    public function cleanImageCache(Varien_Event_Observer $observer)
    {
        $cacheDir = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'cache';
        mageDelTree($cacheDir);
    }
}