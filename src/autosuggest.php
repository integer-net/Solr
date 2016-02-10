<?php
use IntegerNet\SolrSuggest\CacheBackend\File\CacheItemPool;
use IntegerNet\SolrSuggest\Plain\Cache\PsrCache;
use IntegerNet\SolrSuggest\Plain\Factory;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

class IntegerNet_Solr_Autosuggest
{
    /**
     * Default lib base dir. Adjust it here if this is different, the Magento configuration cannot be
     * loaded without initialized autoloader.
     *
     * @todo documentation: customize autosuggest.php
     * @todo maybe add autosuggest.config.php[.dist] for base dir and cache
     * @return string
     */
    protected function getLibBaseDir()
    {
        // use real directory of current file in case of symlinks
        return __DIR__ . '/lib/IntegerNet_Solr';
    }

    /**
     * @return string
     */
    protected function getCacheBaseDir()
    {
        return 'var/cache/integernet_solr';
    }

    /**
     * @return PsrCache
     */
    protected function getCache()
    {
        return new PsrCache(new CacheItemPool($this->getCacheBaseDir()));
    }

    public function __construct()
    {
        // Varien_Autoload only as long as Logger uses Zend_Log
        set_include_path(get_include_path() . PATH_SEPARATOR . realpath('lib'));
        require_once 'lib/Varien/Autoload.php';
        Varien_Autoload::register();

        require_once 'app/code/community/IntegerNet/Solr/Helper/Autoloader.php';
        IntegerNet_Solr_Helper_Autoloader::createAndRegisterWithBaseDir($this->getLibBaseDir());
    }

    public function run()
    {
        $request = AutosuggestRequest::fromGet($_GET);
        $factory = new Factory($request, $this->getCache());
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