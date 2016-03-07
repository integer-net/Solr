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

use IntegerNet\Solr\Config\ServerConfig;

class ServerConfigBuilder
{
    /*
     * Default values
     */
    private $host = 'localhost',
        $port = 8983,
        $path= 'solr',
        $core = self::DEFAULT_CORE,
        $swapCore = '',
        $useHttps = false,
        $httpMethod = 'GET',
        $useHttpBasicAuth = false,
        $httpBasicAuthUsername = '',
        $httpBasicAuthPassword = '';

    const DEFAULT_CORE = 'core0';
    const SWAP_CORE = 'core1';

    private function __construct()
    {
    }
    public static function defaultConfig()
    {
        return new static;
    }
    public static function swapCoreConfig()
    {
        return self::defaultConfig()->withSwapCore(self::SWAP_CORE);
    }

    /**
     * @param string $host
     * @return $this
     */
    public function withHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function withPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function withPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param string $core
     * @return $this
     */
    public function withCore($core)
    {
        $this->core = $core;
        return $this;
    }

    /**
     * @param string $swapCore
     * @return $this
     */
    public function withSwapCore($swapCore)
    {
        $this->swapCore = $swapCore;
        return $this;
    }

    /**
     * @param boolean $useHttps
     * @return $this
     */
    public function withUseHttps($useHttps)
    {
        $this->useHttps = $useHttps;
        return $this;
    }

    /**
     * @param string $httpMethod
     * @return $this
     */
    public function withHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    /**
     * @param boolean $useHttpBasicAuth
     * @return $this
     */
    public function withUseHttpBasicAuth($useHttpBasicAuth)
    {
        $this->useHttpBasicAuth = $useHttpBasicAuth;
        return $this;
    }

    /**
     * @param string $httpBasicAuthUsername
     * @return $this
     */
    public function withHttpBasicAuthUsername($httpBasicAuthUsername)
    {
        $this->httpBasicAuthUsername = $httpBasicAuthUsername;
        return $this;
    }

    /**
     * @param string $httpBasicAuthPassword
     * @return $this
     */
    public function withHttpBasicAuthPassword($httpBasicAuthPassword)
    {
        $this->httpBasicAuthPassword = $httpBasicAuthPassword;
        return $this;
    }


    public function build()
    {
        return new ServerConfig(
            $this->host, $this->port, $this->path, $this->core, $this->swapCore, $this->useHttps, $this->httpMethod,
            $this->useHttpBasicAuth, $this->httpBasicAuthUsername, $this->httpBasicAuthPassword);
    }
}