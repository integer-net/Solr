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
 */
class IntegerNet_Solr_Autosuggest_Config
{
    /** @var string */
    private $cacheBaseDir;
    /** @var  string */
    private $libBaseDir;

    /**
     * @param string $libBaseDir
     * @param string $cacheBaseDir
     */
    private function __construct($libBaseDir, $cacheBaseDir)
    {
        $this->libBaseDir = $libBaseDir;
        $this->cacheBaseDir = $cacheBaseDir;
    }

    public static function defaultConfig()
    {
        // use real directory of current file in case of symlinks
        $libBaseDir = __DIR__ . '/lib/IntegerNet_Solr';
        // use directory relative to cwd (Magento root)
        $cacheBaseDir = 'var/cache/integernet_solr';
        return new self($libBaseDir, $cacheBaseDir);
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
        $request = \IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest::fromGet($_GET);
        $factory = new \IntegerNet\SolrSuggest\Plain\Factory($request, $this->getCache());
        $controller = $factory->getAutosuggestController();

        $response = $controller->process($request);

        if (function_exists('http_response_code')) {
            \http_response_code($response->getStatus());
        }
        echo $response->getBody();
    }

}

$autosuggest = new IntegerNet_Solr_Autosuggest();

$autosuggest->run();