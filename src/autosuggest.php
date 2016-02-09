<?php
use IntegerNet\SolrSuggest\Plain\Factory;

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
    public function __construct()
    {
        // Varien_Autoload only as long as Logger uses Zend_Log
        set_include_path(get_include_path() . PATH_SEPARATOR . realpath('lib'));
        require_once 'lib/Varien/Autoload.php';
        Varien_Autoload::register();

        require_once 'app/code/community/IntegerNet/Solr/Helper/Autoloader.php';
        IntegerNet_Solr_Helper_Autoloader::createAndRegisterWithBaseDir($this->getLibBaseDir());
    }

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
        return __DIR__ . '/lib/IntegerNet_Solr';
    }
    
    public function printHtml()
    {
        $request = \IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest::fromGet($_GET);
        $factory = new Factory($request, new \IntegerNet\SolrSuggest\Plain\Cache\PsrCache(
            new \IntegerNet\SolrSuggest\CacheBackend\File\CacheItemPool('var/cache/integernet_solr')));

        //TODO extract everything below into factory & controller

        $config = $factory->getLoadedCacheReader($request->getStoreId())->getConfig($request->getStoreId());
        $block = $factory->getAutosuggestBlock();

        $controller = new \IntegerNet\SolrSuggest\Plain\AutosuggestController(
            $config->getGeneralConfig(), $block
        );
        $response = $controller->process($request);

        if (function_exists('http_response_code')) {
            \http_response_code($response->getStatus());
        }
        echo $response->getBody();
    }

}

$autosuggest = new IntegerNet_Solr_Autosuggest();

$autosuggest->printHtml();