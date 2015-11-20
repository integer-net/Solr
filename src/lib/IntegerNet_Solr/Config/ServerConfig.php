<?php
namespace IntegerNet\Solr\Config;
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class ServerConfig
{
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $core;
    /**
     * @var string
     */
    private $swapCore;
    /**
     * @var bool
     */
    private $useHttps;
    /**
     * @var string
     */
    private $httpTransportMethod;
    /**
     * @var bool
     */
    private $useHttpBasicAuth;
    /**
     * @var string
     */
    private $httpBasicAuthUsername;
    /**
     * @var string
     */
    private $httpBasicAuthPassword;

    /**
     * @param string $host
     * @param int $port
     * @param string $path
     * @param string $core
     * @param string $swapCore
     * @param bool $useHttps
     * @param string $httpMethod
     * @param bool $useHttpBasicAuth
     * @param string $httpBasicAuthUsername
     * @param string $httpBasicAuthPassword
     */
    public function __construct($host, $port, $path, $core, $swapCore, $useHttps, $httpMethod, $useHttpBasicAuth, $httpBasicAuthUsername, $httpBasicAuthPassword)
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->core = $core;
        $this->swapCore = $swapCore;
        $this->useHttps = $useHttps;
        $this->httpTransportMethod = $httpMethod;
        $this->useHttpBasicAuth = $useHttpBasicAuth;
        $this->httpBasicAuthUsername = $httpBasicAuthUsername;
        $this->httpBasicAuthPassword = $httpBasicAuthPassword;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getCore()
    {
        return $this->core;
    }

    /**
     * Return unique resource identifier for solr core
     *
     * @return string
     */
    public function getServerInfo()
    {
        return sprintf('%s:%s%s%s', $this->host, $this->port, $this->path, $this->core);
    }

    /**
     * @return boolean
     */
    public function isUseHttps()
    {
        return $this->useHttps;
    }

    /**
     * @return string
     */
    public function getHttpTransportMethod()
    {
        return $this->httpTransportMethod;
    }

    /**
     * @return boolean
     */
    public function isUseHttpBasicAuth()
    {
        return $this->useHttpBasicAuth;
    }

    /**
     * @return string
     */
    public function getHttpBasicAuthUsername()
    {
        return $this->httpBasicAuthUsername;
    }

    /**
     * @return string
     */
    public function getHttpBasicAuthPassword()
    {
        return $this->httpBasicAuthPassword;
    }

    /**
     * @return string
     */
    public function getSwapCore()
    {
        return $this->swapCore;
    }
}