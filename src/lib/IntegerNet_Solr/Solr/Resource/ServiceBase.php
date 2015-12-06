<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
namespace IntegerNet\Solr\Resource;

use Apache_Solr_Service;
use Apache_Solr_HttpTransport_Interface;
use Apache_Solr_Compatibility_CompatibilityLayer;
use Apache_Solr_InvalidArgumentException;
use Apache_Solr_HttpTransportException;
use Apache_Solr_Response;

/**
 * To split/add additional features, extend this class and use appendService() to chain multiple services
 *
 * @package IntegerNet\Solr\Resource
 */
class ServiceBase extends Apache_Solr_Service
{
    const SUGGEST_SERVLET = 'suggest';
    const CORES_SERVLET = 'admin/cores';
    const INFO_SERVLET = 'admin/info/system';

    /**
     * @var ServiceBase Next instance in chain of responsibility
     */
    private $successor;

    /**
     * Constructed servlet full path URLs
     *
     * @var string
     */
    protected $_suggestUrl;
    protected $_coresUrl;
    protected $_infoUrl;

    protected $_basePath;
    protected $_useHttps;

    /**
     * Constructor. All parameters are optional and will take on default values
     * if not specified.
     *
     * @param string $host
     * @param int|string $port
     * @param string $path
     * @param Apache_Solr_HttpTransport_Interface|bool $httpTransport
     * @param Apache_Solr_Compatibility_CompatibilityLayer|bool $compatibilityLayer
     * @param bool $useHttps
     * @throws Apache_Solr_InvalidArgumentException
     */
    public function __construct(
        $host = 'localhost',
        $port = 8180,
        $path = '/solr/',
        $httpTransport = false,
        $compatibilityLayer = false,
        $useHttps = false
    ) {
        $this->setUseHttps($useHttps);
        parent::__construct($host, $port, $path, $httpTransport, $compatibilityLayer);
    }

    /**
     * Append service to chain of responsibility
     *
     * @param ServiceBase $successor
     */
    public function appendService(ServiceBase $successor)
    {
        if ($this->successor !== null) {
            $this->successor->appendService($successor);
        } else {
            $this->successor = $successor;
        }
    }

    public function __call($method, $args)
    {
        if ($this->successor !== null) {
            return call_user_func_array(array($this->successor, $method), $args);
        }
        throw new \BadMethodCallException(sprintf('Method %s::%s() not found.', get_called_class(), $method));
    }
    
    /**
     * @param string $basePath
     * @return ServiceBase
     * @throws Exception
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = $basePath;
        $this->_coresUrl = $this->_constructBaseUrl(self::CORES_SERVLET);
        $this->_infoUrl = $this->_constructBaseUrl(self::INFO_SERVLET);
        return $this;
    }

    /**
     * @param bool $useHttps
     */
    public function setUseHttps($useHttps) {
        $this->_useHttps = $useHttps;
    }

    /**
     * Construct the Full URLs for the three servlets we reference
     */
    protected function _initUrls()
    {
        //Initialize our full servlet URLs now that we have server information
        $this->_suggestUrl = $this->_constructUrl(self::SUGGEST_SERVLET);

        parent::_initUrls();
    }

    /**
     * Return a valid http URL given this server's host, port and path and a provided servlet name
     * Exclude core name from 
     *
     * @param string $servlet
     * @param array $params
     * @return string
     * @throws Exception
     */
    protected function _constructBaseUrl($servlet, $params = array())
    {
        if (count($params))
        {
            //escape all parameters appropriately for inclusion in the query string
            $escapedParams = array();

            foreach ($params as $key => $value)
            {
                $escapedParams[] = urlencode($key) . '=' . urlencode($value);
            }

            $queryString = $this->_queryDelimiter . implode($this->_queryStringDelimiter, $escapedParams);
        }
        else
        {
            $queryString = '';
        }
        
        if (!$this->_basePath) {
            throw new Exception('Please provide a base path');
        }

        $protocol = 'http://';
        if ($this->_useHttps) {
            $protocol = 'https://';
        }
        return $protocol . $this->_host . ':' . $this->_port . $this->_basePath . $servlet . $queryString;
    }

    /**
     * Return a valid http URL given this server's host, port and path and a provided servlet name
     *
     * @param string $servlet
     * @param array $params
     * @return string
     */
    protected function _constructUrl($servlet, $params = array())
    {
        if (count($params))
        {
            //escape all parameters appropriately for inclusion in the query string
            $escapedParams = array();

            foreach ($params as $key => $value)
            {
                $escapedParams[] = urlencode($key) . '=' . urlencode($value);
            }

            $queryString = $this->_queryDelimiter . implode($this->_queryStringDelimiter, $escapedParams);
        }
        else
        {
            $queryString = '';
        }

        $protocol = 'http://';
        if ($this->_useHttps) {
            $protocol = 'https://';
        }
        return $protocol . $this->_host . ':' . $this->_port . $this->_path . $servlet . $queryString;
    }

    /**
     * core swap interface
     *
     * @param string $core
     * @param string $otherCore
     * @param string $method The HTTP method (Service::METHOD_GET or Service::METHOD::POST)
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     * @throws Apache_Solr_InvalidArgumentException If an invalid HTTP method is used
     * @throws Exception
     */
    public function swapCores($core, $otherCore, $method = self::METHOD_GET)
    {
        if (!$this->_coresUrl) {
            throw new Exception('Please call "setBasePath" before.');
        }

        $params = array();

        // construct our full parameters
        $params['action'] = 'SWAP';
        $params['core'] = $core;
        $params['other'] = $otherCore;

        $queryString = $this->_generateQueryString($params);

        if ($method == self::METHOD_GET)
        {
            return $this->_sendRawGet($this->_coresUrl . $this->_queryDelimiter . $queryString);
        }
        else if ($method == self::METHOD_POST)
        {
            return $this->_sendRawPost($this->_coresUrl, $queryString, FALSE, 'application/x-www-form-urlencoded; charset=UTF-8');
        }
        else
        {
            throw new Apache_Solr_InvalidArgumentException("Unsupported method '$method', please use the Service::METHOD_* constants");
        }
    }

    /**
     * admin info interface
     *
     * @param string $method The HTTP method (Service::METHOD_GET or Service::METHOD::POST)
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     * @throws Apache_Solr_InvalidArgumentException If an invalid HTTP method is used
     * @throws Exception
     */
    public function info($method = self::METHOD_GET)
    {
        if (!$this->_infoUrl) {
            throw new Exception('Please call "setBasePath" before.');
        }

        $params = array();

        // construct our full parameters
        $params['wt'] = 'json';

        $queryString = $this->_generateQueryString($params);

        if ($method == self::METHOD_GET)
        {
            return $this->_sendRawGet($this->_infoUrl . $this->_queryDelimiter . $queryString);
        }
        else if ($method == self::METHOD_POST)
        {
            return $this->_sendRawPost($this->_infoUrl, $queryString, FALSE, 'application/x-www-form-urlencoded; charset=UTF-8');
        }
        else
        {
            throw new Apache_Solr_InvalidArgumentException("Unsupported method '$method', please use the Service::METHOD_* constants");
        }
    }
}