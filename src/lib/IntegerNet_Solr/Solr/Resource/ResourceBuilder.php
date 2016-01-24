<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Resource;
use Apache_Solr_Compatibility_Solr4CompatibilityLayer;
use Apache_Solr_HttpTransport_Interface;
use Apache_Solr_HttpTransport_Abstract;
use Apache_Solr_HttpTransport_Curl;
use Apache_Solr_HttpTransport_FileGetContents;
use IntegerNet\Solr\Config\ServerConfig;
use IntegerNet\SolrCategories\Resource\ServiceCategories;
use IntegerNet\SolrSuggest\Resource\ServiceSuggest;

class ResourceBuilder
{
    /**
     * @var string
     */
    private $host = 'localhost';
    /**
     * @var string
     */
    private $port = '8180';
    /**
     * @var string
     */
    private $path = '/solr/';
    /**
     * @var false|Apache_Solr_HttpTransport_Interface
     */
    private $httpTransportAdapter = false;
    /**
     * @var $compatibilityLayer false|Apache_Solr_Compatibility_CompatibilityLayer
     */
    private $compatibilityLayer = false;
    /**
     * @var $useHttps bool
     */
    private $useHttps = false;

    public function __construct()
    {
        $this->compatibilityLayer = new Apache_Solr_Compatibility_Solr4CompatibilityLayer();
    }

    /**
     * @return ServiceBase
     */
    public function build()
    {
        $service = new ServiceBase(
            $this->host, $this->port, $this->path,
            $this->httpTransportAdapter, $this->compatibilityLayer, $this->useHttps);
        $service->appendService(new ServiceCategories(
                $this->host, $this->port, $this->path,
                $this->httpTransportAdapter, $this->compatibilityLayer, $this->useHttps)
        );
        $service->appendService(new ServiceSuggest(
                $this->host, $this->port, $this->path,
                $this->httpTransportAdapter, $this->compatibilityLayer, $this->useHttps)
        );
        return $service;
    }
    /**
     * Returns new instance with default values for method chaining
     *
     * @return ResourceBuilder
     */
    public static function defaultResource()
    {
        return new static;
    }

    /**
     * @param ResourceBuilder $source
     * @return ResourceBuilder
     */
    public static function withConfigFrom(ResourceBuilder $source)
    {
        /** @var ResourceBuilder $builder */
        $builder = new static;
        $builder->setHost($source->host)
            ->setPort($source->port)
            ->setPath($source->path)
            ->setHttpTransportAdapter($source->httpTransportAdapter)
            ->setCompatibilityLayer($source->compatibilityLayer)
            ->setUseHttps($source->useHttps);
        return $builder;
    }

    /**
     * @param ServerConfig $serverConfig
     * @param bool|false $useSwapCore
     * @return ResourceBuilder
     */
    public function withConfig(ServerConfig $serverConfig, $useSwapCore = false)
    {
        $host = $serverConfig->getHost();
        $port = $serverConfig->getPort();
        $path = $serverConfig->getPath();
        $core = $serverConfig->getCore();
        $useHttps = $serverConfig->isUseHttps();
        if ($useSwapCore) {
            $core = $serverConfig->getSwapCore();
        }
        if ($core) {
            $path .= $core . '/';
        }

        $builder = clone $this;
        return $builder->setHost($host)->setPort($port)->setPath($path)->setUseHttps($useHttps)
            ->setHttpTransportAdapter(self::getHttpTransportAdapter($serverConfig));
    }

    /**
     * Create HttpTransportAdapter based on configuration
     *
     * @param $serverConfig
     * @return Apache_Solr_HttpTransport_Abstract
     */
    private static function getHttpTransportAdapter($serverConfig)
    {
        switch ($serverConfig->getHttpTransportMethod()) {
            case HttpTransportMethod::HTTP_TRANSPORT_METHOD_CURL:
                $adapter = new Apache_Solr_HttpTransport_Curl();
                break;
            default:
                $adapter = new Apache_Solr_HttpTransport_FileGetContents();
        }

        if ($serverConfig->isUseHttpBasicAuth()) {
            $adapter->setAuthenticationCredentials(
                $serverConfig->getHttpBasicAuthUsername(),
                $serverConfig->getHttpBasicAuthPassword()
            );
        }

        return $adapter;
    }
    /**
     * @param string $host
     * @return ResourceBuilder
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param string $port
     * @return ResourceBuilder
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $path
     * @return ResourceBuilder
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param false|Apache_Solr_HttpTransport_Interface $httpTransportAdapter
     * @return ResourceBuilder
     */
    public function setHttpTransportAdapter($httpTransportAdapter)
    {
        $this->httpTransportAdapter = $httpTransportAdapter;
        return $this;
    }

    /**
     * @param false|Apache_Solr_Compatibility_CompatibilityLayer $compatibilityLayer
     * @return ResourceBuilder
     */
    public function setCompatibilityLayer($compatibilityLayer)
    {
        $this->compatibilityLayer = $compatibilityLayer;
        return $this;
    }

    /**
     * @param boolean $useHttps
     * @return ResourceBuilder
     */
    public function setUseHttps($useHttps)
    {
        $this->useHttps = $useHttps;
        return $this;
    }
    
    
}