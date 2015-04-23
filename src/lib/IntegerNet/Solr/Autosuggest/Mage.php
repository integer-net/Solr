<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
final class IntegerNet_Solr_Autosuggest_Mage
{
    /** @var IntegerNet_Solr_Autosuggest_Config $_config */
    static private $_config;

    public static function setConfig(IntegerNet_Solr_Autosuggest_Config $config)
    {
        self::$_config = $config;
    }

    /**
     * Retrieve config value for store by path
     *
     * @param string $path
     * @param mixed $store
     * @return mixed
     * @throws Exception
     */
    public static function getStoreConfig($path, $store = null)
    {
        if (is_null(self::$_config)) {
            throw new Exception('Config not set.');
        }

        return self::$_config->getConfigData($path);
    }

    /**
     * Retrieve config flag for store by path
     *
     * @param string $path
     * @param mixed $store
     * @return bool
     */
    public static function getStoreConfigFlag($path, $store = null)
    {
        $flag = strtolower(self::getStoreConfig($path, $store));
        if (!empty($flag) && 'false' !== $flag) {
            return true;
        } else {
            return false;
        }
    }
}