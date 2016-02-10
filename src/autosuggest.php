<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

/*
 * NO USE STATEMENTS BEFORE AUTOLOADER IS INITIALIZED!
 */

/**
 * Class IntegerNet_Solr_Autosuggest_Config used for customization in autosuggest.config.php
 *
 * @todo make this file independent of Magento too
 */
class IntegerNet_Solr_Autosuggest_Config
{
    /** @var string */
    private $cacheBaseDir;
    /** @var  string */
    private $libBaseDir;
    /** @var \Closure */
    private $loadApplicationCallback;

    /**
     * @param string $libBaseDir
     * @param string $cacheBaseDir
     * @param $loadApplicationCallback
     */
    private function __construct($libBaseDir, $cacheBaseDir, $loadApplicationCallback)
    {
        $this->libBaseDir = $libBaseDir;
        $this->cacheBaseDir = $cacheBaseDir;
        $this->loadApplicationCallback = $loadApplicationCallback;
    }

    public static function defaultConfig()
    {
        $libBaseDir = __DIR__ . '/lib/IntegerNet_Solr';
        $cacheBaseDir = '/tmp/integernet_solr';
        $loadApplicationCallback = function () {
            throw new BadMethodCallException('Application cannot be instantiated');
        };
        return new self($libBaseDir, $cacheBaseDir, $loadApplicationCallback);
    }

    /**
     * @param string $libBaseDir
     * @return IntegerNet_Solr_Autosuggest_Config
     */
    public function withLibBaseDir($libBaseDir)
    {
        //TODO allow defining path to external autoloader instead (i.e. composer)
        $config = clone $this;
        $config->libBaseDir = $libBaseDir;
        return $config;
    }
    /**
     * @param string $cacheBaseDir
     * @return IntegerNet_Solr_Autosuggest_Config
     */
    public function withCacheBaseDir($cacheBaseDir)
    {
        $config = clone $this;
        $config->cacheBaseDir = $cacheBaseDir;
        return $config;
    }
    /**
     * @param Closure $callback
     * @return IntegerNet_Solr_Autosuggest_Config
     */
    public function withLoadApplicationCallback(Closure $callback)
    {
        $config = clone $this;
        $config->loadApplicationCallback = $callback;
        return $config;
    }

    /**
     * @return string
     */
    public function getLibBaseDir()
    {
        return $this->libBaseDir;
    }

    /**
     * @return \IntegerNet\SolrSuggest\Plain\Cache\CacheStorage
     */
    public function getCache()
    {
        //TODO allow defining custom callback for cache instantiation
        return new \IntegerNet\SolrSuggest\Plain\Cache\PsrCache(
            new \IntegerNet\SolrSuggest\CacheBackend\File\CacheItemPool($this->cacheBaseDir)
        );
    }

    /**
     * @return Closure
     */
    public function getLoadApplicationCallback()
    {
        return $this->loadApplicationCallback;
    }

}
class IntegerNet_Solr_Autosuggest
{
    /**
     * @var IntegerNet_Solr_Autosuggest_Config
     */
    private $config;
    protected function getLibBaseDir()
    {
        return $this->config->getLibBaseDir();
    }
    /**
     * @return \IntegerNet\SolrSuggest\Plain\Cache\CacheStorage
     */
    protected function getCache()
    {
        return $this->config->getCache();
    }
    /**
     * @return Closure
     */
    protected function getLoadApplicationCallback()
    {
        return $this->config->getLoadApplicationCallback();
    }

    public function __construct()
    {
        $this->initConfig();

        // Varien_Autoload only as long as Logger uses Zend_Log
        set_include_path(get_include_path() . PATH_SEPARATOR . realpath('lib'));
        require_once 'lib/Varien/Autoload.php';
        Varien_Autoload::register();

        require_once 'app/code/community/IntegerNet/Solr/Helper/Autoloader.php';
        IntegerNet_Solr_Helper_Autoloader::createAndRegisterWithBaseDir($this->getLibBaseDir());
    }

    private function initConfig()
    {
        if (file_exists(__DIR__ . '/autosuggest.config.php')) {
            $this->config = include __DIR__ . '/autosuggest.config.php';
            if ($this->config instanceof IntegerNet_Solr_Autosuggest_Config) {
                return;
            }
        }
        $this->config = include __DIR__ . '/autosuggest.config.dist.php';
    }

    public function run()
    {
        try {
            $request = \IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest::fromGet($_GET);
            $factory = new \IntegerNet\SolrSuggest\Plain\Factory($request, $this->getCache(), $this->getLoadApplicationCallback());
            $controller = $factory->getAutosuggestController();

            $response = $controller->process($request);
        } catch (\Exception $e) {
            $response = new \IntegerNet\SolrSuggest\Plain\Http\AutosuggestResponse(500, 'Internal Server Error');
        }

        if (function_exists('http_response_code')) {
            \http_response_code($response->getStatus());
        }
        echo $response->getBody();
    }

}

$autosuggest = new IntegerNet_Solr_Autosuggest();

$autosuggest->run();