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
        // AutosuggestRequest not instantiated yet, needs autoloader
        $storeId = intval($_GET['store_id']);
        $config = $this->_getConfig($storeId);

        if (!class_exists('Mage')) {
            // still needed for: IntegerNet_Solr_Model_Config_Store (getStoreConfig(), getStoreConfigFlag())
            require_once('lib' . DIRECTORY_SEPARATOR . 'IntegerNet' . DIRECTORY_SEPARATOR . 'Solr' . DIRECTORY_SEPARATOR . 'Autosuggest' . DIRECTORY_SEPARATOR . 'Mage.php');
            class_alias('IntegerNet_Solr_Autosuggest_Mage', 'Mage');
        }
        IntegerNet_Solr_Autosuggest_Mage::setConfig($config);
        IntegerNet_Solr_Helper_Autoloader::createAndRegister();

    }
    
    public function printHtml()
    {
        $request = \IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest::fromGet($_GET);
        $factory = new Factory($request, new \IntegerNet\SolrSuggest\Plain\Cache\PsrCache(
            new \IntegerNet\SolrSuggest\CacheBackend\File\CacheItemPool('var/cache/integernet_solr')));

        $config = new IntegerNet_Solr_Model_Config_Store(null);
        $template = new IntegerNet_Solr_Autosuggest_Template();
        $highlighter = new \IntegerNet\SolrSuggest\Util\HtmlStringHighlighter();
        $block = new IntegerNet\SolrSuggest\Plain\Block\Autosuggest($factory, $template, $highlighter);

        $controller = new \IntegerNet\SolrSuggest\Plain\AutosuggestController(
            $config->getGeneralConfig(), $block
        );
        $response = $controller->process($request);

        if (function_exists('http_response_code')) {
            \http_response_code($response->getStatus());
        }
        echo $response->getBody();
    }

    /**
     * @return IntegerNet_Solr_Autosuggest_Config
     */
    protected function _getConfig($storeId)
    {
        require_once('lib' . DIRECTORY_SEPARATOR . 'IntegerNet' . DIRECTORY_SEPARATOR . 'Solr' . DIRECTORY_SEPARATOR . 'Autosuggest' . DIRECTORY_SEPARATOR . 'Config.php');
        return new IntegerNet_Solr_Autosuggest_Config($storeId);
    }
}

$autosuggest = new IntegerNet_Solr_Autosuggest();

$autosuggest->printHtml();