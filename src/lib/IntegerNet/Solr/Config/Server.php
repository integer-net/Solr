<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class IntegerNet_Solr_Config_Server
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
     * @var bool
     */
    private $useHttps;
    /**
     * @var string
     */
    private $httpMethod;
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
     * @param bool $useHttps
     * @param string $httpMethod
     * @param bool $useHttpBasicAuth
     * @param string $httpBasicAuthUsername
     * @param string $httpBasicAuthPassword
     */
    public function __construct($host, $port, $path, $core, $useHttps, $httpMethod, $useHttpBasicAuth, $httpBasicAuthUsername, $httpBasicAuthPassword)
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->core = $core;
        $this->useHttps = $useHttps;
        $this->httpMethod = $httpMethod;
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
     * @return boolean
     */
    public function isUseHttps()
    {
        return $this->useHttps;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
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


}