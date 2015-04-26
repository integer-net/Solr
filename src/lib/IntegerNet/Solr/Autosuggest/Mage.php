<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('BP', dirname(dirname(__FILE__)) . DS . '..' . DS . '..' . DS . '..');

if (defined('COMPILER_INCLUDE_PATH')) {
    $appPath = COMPILER_INCLUDE_PATH;
    set_include_path($appPath . PS . get_include_path());
    include_once COMPILER_INCLUDE_PATH . DS . "Mage_Core_functions.php";
    include_once COMPILER_INCLUDE_PATH . DS . "Varien_Autoload.php";
} else {
    /**
     * Set include path
     */
    $paths = array();
    $paths[] = BP . DS . 'app' . DS . 'code' . DS . 'local';
    $paths[] = BP . DS . 'app' . DS . 'code' . DS . 'community';
    $paths[] = BP . DS . 'app' . DS . 'code' . DS . 'core';
    $paths[] = BP . DS . 'lib';

    $appPath = implode(PS, $paths);
    set_include_path($appPath . PS . get_include_path());
    include_once "Mage/Core/functions.php";
    include_once "Varien/Autoload.php";
}

Varien_Autoload::register();

/**
 * This class is a low weight replacement for the "Mage" class in autosuggest calls
 *
 * Class IntegerNet_Solr_Autosuggest_Mage
 */
final class IntegerNet_Solr_Autosuggest_Mage
{
    /** @var IntegerNet_Solr_Autosuggest_Config */
    static private $_config;

    /** @var IntegerNet_Solr_Autosuggest_App */
    static private $_app;

    /** @var IntegerNet_Solr_Autosuggest_Helper */
    static private $_helper;

    /** @var array */
    static private $_registry = array();

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

    /**
     * Retrieve model object
     *
     * @link    Mage_Core_Model_Config::getModelInstance
     * @param   string $modelClass
     * @param   array|object $arguments
     * @return  Mage_Core_Model_Abstract|false
     */
    public static function getModel($modelClass = '', $arguments = array())
    {
        $className = self::$_config->getModelClassname($modelClass);
        if (!$className) {
            throw new Exception('Class for alias ' . $modelClass . ' not found');
        }
        return new $className;
    }

    /**
     * Retrieve model object
     *
     * @link    Mage_Core_Model_Config::getModelInstance
     * @param   string $modelClass
     * @param   array|object $arguments
     * @return  Mage_Core_Model_Abstract|false
     */
    public static function getResourceModel($modelClass = '', $arguments = array())
    {
        $className = self::$_config->getResourceModelClassname($modelClass);
        if (!$className) {
            throw new Exception('Class for alias ' . $modelClass . ' not found');
        }
        return new $className;
    }

        /**
     * Retrieve model object singleton
     *
     * @param   string $modelClass
     * @param   array $arguments
     * @return  Mage_Core_Model_Abstract
     */
    public static function getSingleton($modelClass='', array $arguments=array())
    {
        $registryKey = '_singleton/'.$modelClass;
        if (!self::registry($registryKey)) {
            self::register($registryKey, self::getModel($modelClass, $arguments));
        }
        return self::registry($registryKey);
    }

    /**
     * Register a new variable
     *
     * @param string $key
     * @param mixed $value
     * @param bool $graceful
     * @throws Mage_Core_Exception
     */
    public static function register($key, $value, $graceful = false)
    {
        if (isset(self::$_registry[$key])) {
            if ($graceful) {
                return;
            }
            self::throwException('Mage registry key "'.$key.'" already exists');
        }
        self::$_registry[$key] = $value;
    }

    /**
     * Unregister a variable from register by key
     *
     * @param string $key
     */
    public static function unregister($key)
    {
        if (isset(self::$_registry[$key])) {
            if (is_object(self::$_registry[$key]) && (method_exists(self::$_registry[$key], '__destruct'))) {
                self::$_registry[$key]->__destruct();
            }
            unset(self::$_registry[$key]);
        }
    }

    /**
     * Retrieve a value from registry by a key
     *
     * @param string $key
     * @return mixed
     */
    public static function registry($key)
    {
        if (isset(self::$_registry[$key])) {
            return self::$_registry[$key];
        }
        return null;
    }

    /**
     * log facility (??)
     *
     * @param string $message
     * @param integer $level
     * @param string $file
     * @param bool $forceLog
     */
    public static function log($message, $level = null, $file = '', $forceLog = false)
    {
        try {
            $logActive = self::getStoreConfig('dev/log/active');
            if (empty($file)) {
                $file = self::getStoreConfig('dev/log/file');
            }
        }
        catch (Exception $e) {
            $logActive = true;
        }

        if (!$logActive && !$forceLog) {
            return;
        }

        $file = empty($file) ? 'system.log' : $file;

        try {
            $logDir  = 'var' . DS . 'log';
            $logFile = $logDir . DS . $file;

            if (!is_dir($logDir)) {
                mkdir($logDir);
                chmod($logDir, 0777);
            }

            if (!file_exists($logFile)) {
                file_put_contents($logFile, '');
                chmod($logFile, 0777);
            }

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            $line = sprintf(
                '%s: %s',
                time(),
                $message
            );
            file_put_contents($logFile, $line, FILE_APPEND);
        }
        catch (Exception $e) {
        }
    }

    public static function app()
    {
        if (is_null(self::$_app)) {
            require_once('lib' . DS . 'IntegerNet' . DS . 'Solr' . DS . 'Autosuggest' . DS . 'App.php');
            self::$_app = new IntegerNet_Solr_Autosuggest_App(self::$_config->getStoreId());
        }
        return self::$_app;
    }

    public static function helper($identifier)
    {
        if (is_null(self::$_helper)) {
            require_once('lib' . DS . 'IntegerNet' . DS . 'Solr' . DS . 'Autosuggest' . DS . 'Helper.php');
            self::$_helper = new IntegerNet_Solr_Autosuggest_Helper();
        }
        return self::$_helper;
    }

    /**
     * @param string $name
     * @param array $data
     * @return Mage_Core_Model_App
     */
    public static function dispatchEvent($name, array $data = array())
    {
    }
}