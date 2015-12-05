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

use Apache_Solr_Compatibility_Solr4CompatibilityLayer;
use Apache_Solr_Document;
use Apache_Solr_HttpTransport_Abstract;
use Apache_Solr_HttpTransport_Curl;
use Apache_Solr_HttpTransport_FileGetContents;
use Apache_Solr_Response;
use IntegerNet\Solr\Exception;
use IntegerNet\Solr\Implementor\Config;
use IntegerNet_Solr_Model_Source_HttpTransportMethod;

/**
 * Solr resource, facade for Apache_Solr library
 */
class ResourceFacade
{
    /**
     * Configuration reader, by store id
     *
     * @var  Config[]
     */
    protected $_config;

    /**
     * Solr service, by store id
     *
     * @var Service[]
     */
    protected $_solr;

    /** @var bool */
    protected $_useSwapIndex = false;

    /**
     * @param Config[] $storeConfig
     */
    public function __construct(array $storeConfig = [])
    {
        $this->_config = $storeConfig;
    }

    /**
     * @param $storeId
     * @return Config
     * @throws Exception
     */
    public function getStoreConfig($storeId)
    {
        $storeId = (int)$storeId;
        if (!isset($this->_config[$storeId])) {
            throw new Exception("Store with ID {$storeId} not found.");
        }
        return $this->_config[$storeId];
    }

    public function setUseSwapIndex($useSwapIndex = true)
    {
        $this->_useSwapIndex = $useSwapIndex;
        return $this;
    }

    /**
     * @param int $storeId
     * @return Service
     */
    public function getSolrService($storeId)
    {
        if (!isset($this->_solr[$storeId])) {

            if (intval(ini_get('default_socket_timeout')) < 300) {
                ini_set('default_socket_timeout', 300);
            }

            $serverConfig = $this->getStoreConfig($storeId)->getServerConfig();
            $indexingConfig = $this->getStoreConfig($storeId)->getIndexingConfig();
            $host = $serverConfig->getHost();
            $port = $serverConfig->getPort();
            $path = $serverConfig->getPath();
            $core = $serverConfig->getCore();
            $useHttps = $serverConfig->isUseHttps();
            if ($this->_useSwapIndex) {
                $core = $serverConfig->getSwapCore();
            }
            if ($core) {
                $path .= $core . '/';
            }
            $this->_solr[$storeId] = new Service($host, $port, $path, $this->_getHttpTransportAdapter($storeId), new Apache_Solr_Compatibility_Solr4CompatibilityLayer($storeId), $useHttps);
        }
        return $this->_solr[$storeId];
    }

    /**
     * @param $storeId
     * @internal used for testing
     */
    public function setSolrService($storeId, $service)
    {
        $this->_solr[$storeId] = $service;
    }

    /**
     * @param int $storeId
     * @param string $query The raw query string
     * @param int $offset The starting offset for result documents
     * @param int $limit The maximum number of result documents to return
     * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
     * @return Apache_Solr_Response
     */
    public function search($storeId, $query, $offset = 0, $limit = 10, $params = array())
    {
        $response = $this->getSolrService($storeId)->search($query, $offset, $limit, $params);
        return $response;
    }

    /**
     * @param int $storeId
     * @param string $query The raw query string
     * @param int $offset The starting offset for result documents
     * @param int $limit The maximum number of result documents to return
     * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
     * @return Apache_Solr_Response
     */
    public function suggest($storeId, $query, $offset = 0, $limit = 10, $params = array())
    {
        $response = $this->getSolrService($storeId)->suggest($query, $offset, $limit, $params);
        return $response;
    }

    /**
     * @param null|int $restrictToStore
     * @throws Exception
     */
    public function checkSwapCoresConfiguration($restrictToStore = null)
    {
        $coresToSwap = array();
        $coresNotToSwap = array();
        $swapCoreNames = array();

        foreach ($this->_config as $storeId => $storeConfig) {
            /** @var Config $storeConfig */
            $solrServerInfo = $storeConfig->getServerConfig()->getServerInfo();

            if (!is_null($restrictToStore) && ($restrictToStore != $storeId)) {
                continue;
            }

            if (!$storeConfig->getGeneralConfig()->isActive()) {
                continue;
            }

            if ($storeConfig->getIndexingConfig()->isSwapCores()) {
                $coresToSwap[$storeId] = $solrServerInfo;
                $swapCoreNames[$solrServerInfo][$storeId] = $storeConfig->getServerConfig()->getSwapCore();
            } else {
                $coresNotToSwap[$storeId] = $solrServerInfo;
            }
        }

        if (sizeof(array_intersect($coresToSwap, $coresNotToSwap))) {
            throw new Exception('Configuration Error: Activate Core Swapping for all Store Views using the same Solr Core.');
        }

        foreach ($swapCoreNames as $swapCoreNamesByCore) {
            if (sizeof(array_unique($swapCoreNamesByCore)) > 1) {
                throw new Exception('Configuration Error: A Core must swap with the same Core for all Store Views using it.');
            }
        }
    }

    /**
     * @param null|int $restrictToStore
     */
    public function swapCores($restrictToStore = null)
    {
        $storeIdsToSwap = array();

        foreach ($this->_config as $storeId => $storeConfig) {
            /** @var Config $storeConfig */
            $solrServerInfo = $storeConfig->getServerConfig()->getServerInfo();

            if (!is_null($restrictToStore) && ($restrictToStore != $storeId)) {
                continue;
            }

            if (!$storeConfig->getGeneralConfig()->isActive()) {
                continue;
            }

            if ($storeConfig->getIndexingConfig()->isSwapCores()) {
                $storeIdsToSwap[$solrServerInfo] = $storeId;
            }
        }

        foreach ($storeIdsToSwap as $storeIdToSwap) {
            $this->getSolrService($storeIdToSwap)
                ->setBasePath($this->getStoreConfig($storeIdToSwap)->getServerConfig()->getPath())
                ->swapCores(
                    $this->getStoreConfig($storeIdToSwap)->getServerConfig()->getCore(),
                    $this->getStoreConfig($storeIdToSwap)->getServerConfig()->getSwapCore()
                );
        }
    }

    /**
     * @param int $storeId
     * @return null|Apache_Solr_Response
     */
    public function getInfo($storeId)
    {
        if (!$this->getStoreConfig($storeId)->getServerConfig()->getCore()) {
            return null;
        }
        try {
            return $this->getSolrService($storeId)
                ->setBasePath($this->getStoreConfig($storeId)->getServerConfig()->getPath())
                ->info();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param int $storeId
     * @param array $data
     * @return Apache_Solr_Response
     */
    public function addDocument($storeId, $data)
    {
        $document = new Apache_Solr_Document();
        foreach ($data as $key => $value) {
            if ($key == '_boost') {
                $document->setBoost($value);
                continue;
            }
            if (substr($key, -6) == '_boost') {
                $document->setFieldBoost(substr($key, 0, -6), $value);
                continue;
            }
            $document->addField($key, $value);
        }
        $response = $this->getSolrService($storeId)->addDocument($document);
        $this->getSolrService($storeId)->commit();
        return $response;
    }

    /**
     * @param int $storeId
     * @param array[] $combinedData
     * @return Apache_Solr_Response
     */
    public function addDocuments($storeId, $combinedData)
    {
        $documents = array();
        foreach ($combinedData as $data) {

            $document = new Apache_Solr_Document();
            foreach ($data as $key => $value) {
                if ($key == '_boost') {
                    $document->setBoost($value);
                    continue;
                }
                if (substr($key, -6) == '_boost') {
                    $document->setFieldBoost(substr($key, 0, -6), $value);
                    continue;
                }
                if (is_array($value)) {
                    foreach ($value as $subValue) {
                        $document->addField($key, $subValue);
                    }
                } else {
                    $document->addField($key, $value);
                }
            }
            $documents[] = $document;
        }

        $response = $this->getSolrService($storeId)->addDocuments($documents);
        $this->getSolrService($storeId)->commit();
        return $response;
    }

    /**
     * @param int $storeId
     * @return Apache_Solr_Response
     */
    public function deleteAllDocuments($storeId)
    {
        $response = $this->getSolrService($storeId)->deleteByQuery('store_id:' . $storeId);
        $this->getSolrService($storeId)->commit();
        return $response;
    }

    /**
     * @param int $storeId
     * @param string[] $ids
     * @return Apache_Solr_Response
     */
    public function deleteByMultipleIds($storeId, $ids)
    {
        $response = $this->getSolrService($storeId)->deleteByMultipleIds($ids);
        $this->getSolrService($storeId)->commit();
        return $response;
    }

    /**
     * @param int $storeId
     * @return Apache_Solr_HttpTransport_Abstract
     */
    protected function _getHttpTransportAdapter($storeId)
    {
        switch ($this->getStoreConfig($storeId)->getServerConfig()->getHttpTransportMethod()) {
            case IntegerNet_Solr_Model_Source_HttpTransportMethod::HTTP_TRANSPORT_METHOD_CURL:
                $adapter = new Apache_Solr_HttpTransport_Curl();
                break;
            default:
                $adapter = new Apache_Solr_HttpTransport_FileGetContents();
        }

        if ($this->getStoreConfig($storeId)->getServerConfig()->isUseHttpBasicAuth()) {
            $adapter->setAuthenticationCredentials(
                $this->getStoreConfig($storeId)->getServerConfig()->getHttpBasicAuthUsername(),
                $this->getStoreConfig($storeId)->getServerConfig()->getHttpBasicAuthPassword()
            );
        }

        return $adapter;
    }
}