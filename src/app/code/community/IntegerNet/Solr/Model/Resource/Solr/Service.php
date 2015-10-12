<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Resource_Solr_Service extends Apache_Solr_Service
{
    const SUGGEST_SERVLET = 'suggest';
    const CORES_SERVLET = 'admin/cores';
    const INFO_SERVLET = 'admin/info/system';

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
     * @param string $port
     * @param string $path
     * @param Apache_Solr_HttpTransport_Interface $httpTransport
     * @param Apache_Solr_Compatibility_CompatibilityLayer $compatibilityLayer
     * @param bool $useHttps
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
     * @param string $basePath
     * @return IntegerNet_Solr_Model_Resource_Solr_Service
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
     * Simple Suggest interface
     *
     * @param string $query The raw query string
     * @param int $offset The starting offset for result documents
     * @param int $limit The maximum number of result documents to return
     * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
     * @param string $method The HTTP method (IntegerNet_Solr_Model_Resource_Solr_Service::METHOD_GET or IntegerNet_Solr_Model_Resource_Solr_Service::METHOD::POST)
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     * @throws Apache_Solr_InvalidArgumentException If an invalid HTTP method is used
     */
    public function suggest($query, $offset = 0, $limit = 10, $params = array(), $method = self::METHOD_GET)
    {
        // ensure params is an array
        if (!is_null($params))
        {
            if (!is_array($params))
            {
                // params was specified but was not an array - invalid
                throw new Apache_Solr_InvalidArgumentException("\$params must be a valid array or null");
            }
        }
        else
        {
            $params = array();
        }

        // construct our full parameters

        // common parameters in this interface
        $params['wt'] = self::SOLR_WRITER;
        $params['json.nl'] = $this->_namedListTreatment;

        $params['q'] = $query;
        $params['start'] = $offset;
        $params['rows'] = $limit;

        $queryString = $this->_generateQueryString($params);

        if ($method == self::METHOD_GET)
        {
            return $this->_sendRawGet($this->_suggestUrl . $this->_queryDelimiter . $queryString);
        }
        else if ($method == self::METHOD_POST)
        {
            return $this->_sendRawPost($this->_suggestUrl, $queryString, FALSE, 'application/x-www-form-urlencoded; charset=UTF-8');
        }
        else
        {
            throw new Apache_Solr_InvalidArgumentException("Unsupported method '$method', please use the IntegerNet_Solr_Model_Resource_Solr_Service::METHOD_* constants");
        }
    }

    /**
     * core swap interface
     *
     * @param string $core
     * @param string $otherCore
     * @param string $method The HTTP method (IntegerNet_Solr_Model_Resource_Solr_Service::METHOD_GET or IntegerNet_Solr_Model_Resource_Solr_Service::METHOD::POST)
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
            throw new Apache_Solr_InvalidArgumentException("Unsupported method '$method', please use the IntegerNet_Solr_Model_Resource_Solr_Service::METHOD_* constants");
        }
    }

    /**
     * admin info interface
     *
     * @param string $method The HTTP method (IntegerNet_Solr_Model_Resource_Solr_Service::METHOD_GET or IntegerNet_Solr_Model_Resource_Solr_Service::METHOD::POST)
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
            throw new Apache_Solr_InvalidArgumentException("Unsupported method '$method', please use the IntegerNet_Solr_Model_Resource_Solr_Service::METHOD_* constants");
        }
    }
}