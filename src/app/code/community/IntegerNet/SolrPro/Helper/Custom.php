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
 * Note that if you use plain PHP mode, this class will be instantiated without access to "Mage".
 * Any additional data that you have to read here, should be added to the custom cache, using the
 * integernet_solr_autosuggest_config event. It receives "store_id" and "transport" parameters and
 * is dispatched in IntegerNet\SolrSuggest\Plain\Cache\CacheWriter.
 *
 * # Example:
 *
 * ## Write configuration data to custom cache:
 *
 * <code>
 *     $observer->getTransport()->setData('foo/bar', Mage::getConfigData('foo/bar', $observer->getStoreId()));
 * </code>
 *
 * ## Read this data in the helper:
 *
 * <code>
 *     $this->getCacheData('foo/bar')
 * </code>
 */
class IntegerNet_SolrPro_Helper_Custom extends \IntegerNet\SolrSuggest\Block\AbstractCustomHelper
{

}