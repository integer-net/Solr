<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Config\Stub;

use IntegerNet\Solr\Config\StoreConfig;

class StoreConfigBuilder
{
    /*
     * Default values
     */
    private $baseUrl = 'http://www.example.com/';

    private function __construct()
    {
    }

    public static function defaultConfig()
    {
        return new static;
    }

    /**
     * @param string $baseUrl
     * @return StoreConfigBuilder
     */
    public function withBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }
    

    public function build()
    {
        return new StoreConfig($this->baseUrl
        );
    }
}