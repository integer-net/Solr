<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Resource_Solr extends Mage_Core_Model_Resource_Abstract
{
    /** @var IntegerNet_Solr_Model_Resource_Solr_Service[] */
    protected $_solr;
    
    /** @var bool */
    protected $_useSwapIndex = false;
    
    protected function _construct()
    {

    }

    /**
     * Retrieve connection for read data
     */
    protected function _getReadAdapter() 
    {
        
    }

    /**
     * Retrieve connection for write data
     */
    protected function _getWriteAdapter()
    {
        
    }
    
    public function setUseSwapIndex($useSwapIndex = true)
    {
        $this->_useSwapIndex = $useSwapIndex;
        return $this;
    }

    /**
     * @param int $storeId
     * @return IntegerNet_Solr_Model_Resource_Solr_Service
     */
    public function getSolrService($storeId)
    {
        if (!isset($this->_solr[$storeId])) {

            $host = Mage::getStoreConfig('integernet_solr/server/host', $storeId);
            $port = Mage::getStoreConfig('integernet_solr/server/port', $storeId);
            $path = Mage::getStoreConfig('integernet_solr/server/path', $storeId);
            $core = Mage::getStoreConfig('integernet_solr/server/core', $storeId);
            if ($this->_useSwapIndex) {
                $core = Mage::getStoreConfig('integernet_solr/indexing/swap_core', $storeId);
            }
            if ($core) {
                $path .= $core . '/';
            }
            $this->_solr[$storeId] = new IntegerNet_Solr_Model_Resource_Solr_Service($host, $port, $path, $this->_getHttpTransportAdapter(), new Apache_Solr_Compatibility_Solr4CompatibilityLayer());
        }
        return $this->_solr[$storeId];
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
     * @param null|Mage_Core_Model_Store $restrictToStore
     */
    public function checkSwapCoresConfiguration($restrictToStore = null) 
    {
        $coresToSwap = array();
        $coresNotToSwap = array();
        $swapCoreNames = array();

        foreach(Mage::app()->getStores() as $store) {
            /** @var Mage_Core_Model_Store $store */

            $storeId = $store->getId();

            $solrServerInfo = Mage::getStoreConfig('integernet_solr/server/host', $storeId) .
                Mage::getStoreConfig('integernet_solr/server/port', $storeId) .
                Mage::getStoreConfig('integernet_solr/server/path', $storeId) .
                Mage::getStoreConfig('integernet_solr/server/core', $storeId);

            if (!is_null($restrictToStore) && ($restrictToStore->getId() != $storeId)) {
                continue;
            }

            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
                continue;
            }

            if (!$store->getIsActive()) {
                continue;
            }

            if (Mage::getStoreConfigFlag('integernet_solr/indexing/swap_cores', $storeId)) {
                $coresToSwap[$storeId] = $solrServerInfo;
                $swapCoreNames[$solrServerInfo][$storeId] = Mage::getStoreConfig('integernet_solr/indexing/swap_core', $storeId);
            } else {
                $coresNotToSwap[$storeId] = $solrServerInfo;
            }
        }

        if (sizeof(array_intersect($coresToSwap, $coresNotToSwap))) {
            Mage::throwException('Configuration Error: Activate Core Swapping for all Store Views using the same Solr Core.');
        }

        foreach($swapCoreNames as $swapCoreNamesByCore) {
            if (sizeof(array_unique($swapCoreNamesByCore)) > 1) {
                Mage::throwException('Configuration Error: A Core must swap with the same Core for all Store Views using it.');
            }
        }
    }

    /**
     * @param null|Mage_Core_Model_Store $restrictToStore
     */
    public function swapCores($restrictToStore = null)
    {
        $storeIdsToSwap = array();
        
        foreach(Mage::app()->getStores() as $store) {
            /** @var Mage_Core_Model_Store $store */

            $storeId = $store->getId();

            $solrServerInfo = Mage::getStoreConfig('integernet_solr/server/host', $storeId) .
                Mage::getStoreConfig('integernet_solr/server/port', $storeId) .
                Mage::getStoreConfig('integernet_solr/server/path', $storeId) .
                Mage::getStoreConfig('integernet_solr/server/core', $storeId);

            if (!is_null($restrictToStore) && ($restrictToStore->getId() != $storeId)) {
                continue;
            }

            if (!Mage::getStoreConfigFlag('integernet_solr/general/is_active', $storeId)) {
                continue;
            }

            if (!$store->getIsActive()) {
                continue;
            }

            if (Mage::getStoreConfigFlag('integernet_solr/indexing/swap_cores', $storeId)) {
                $storeIdsToSwap[$solrServerInfo] = $storeId;
            }
        }
        
        foreach($storeIdsToSwap as $storeIdToSwap) {
            $this->getSolrService($storeIdToSwap)
                ->setBasePath(Mage::getStoreConfig('integernet_solr/server/path', $storeIdToSwap))
                ->swapCores(
                    Mage::getStoreConfig('integernet_solr/server/core', $storeIdToSwap), 
                    Mage::getStoreConfig('integernet_solr/indexing/swap_core', $storeIdToSwap)
                );
        }
        Mage::log('Swapping cores for stores ' . implode(', ', $storeIdsToSwap));
    }

    /**
     * @param int $storeId
     * @param array $data
     * @return Apache_Solr_Response
     */
    public function addDocument($storeId, $data)
    {
        $document = new Apache_Solr_Document();
        foreach($data as $key => $value) {
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
        foreach($combinedData as $data) {

            $document = new Apache_Solr_Document();
            foreach($data as $key => $value) {
                if ($key == '_boost') {
                    $document->setBoost($value);
                    continue;
                }
                if (substr($key, -6) == '_boost') {
                    $document->setFieldBoost(substr($key, 0, -6), $value);
                    continue;
                }
                if (is_array($value)) {
                    foreach($value as $subValue) {
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
     * @return Apache_Solr_HttpTransport_Abstract
     */
    protected function _getHttpTransportAdapter()
    {
        switch (Mage::getStoreConfig('integernet_solr/server/http_method')) {
            case IntegerNet_Solr_Model_Source_HttpTransportMethod::HTTP_TRANSPORT_METHOD_CURL:
                return new Apache_Solr_HttpTransport_Curl();
            default:
                return new Apache_Solr_HttpTransport_FileGetContents();
        }
    }
}