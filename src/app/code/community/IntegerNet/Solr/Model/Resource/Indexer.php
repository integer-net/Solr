<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Resource_Indexer extends Mage_Core_Model_Resource_Abstract
{
    /** @var Apache_Solr_Service[] */
    protected $_solr;
    
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

    /**
     * @param int $storeId
     * @return Apache_Solr_Service
     */
    public function getSolr($storeId)
    {
        if (!isset($this->_solr[$storeId])) {

            $host = Mage::getStoreConfig('integernet_solr/server/host', $storeId);
            $port = Mage::getStoreConfig('integernet_solr/server/port', $storeId);
            $path = Mage::getStoreConfig('integernet_solr/server/path', $storeId);
            $this->_solr[$storeId] = new Apache_Solr_Service($host, $port, $path, false, new Apache_Solr_Compatibility_Solr4CompatibilityLayer());
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
        $response = $this->getSolr($storeId)->search($query, $offset, $limit, $params);
        return $response;
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
            $document->addField($key, $value);
        }
        $response = $this->getSolr($storeId)->addDocument($document);
        $this->getSolr($storeId)->commit();
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
                $document->addField($key, $value);
            }
            $documents[] = $document;
        }

        $response = $this->getSolr($storeId)->addDocuments($documents);
        $this->getSolr($storeId)->commit();
        return $response;
    }

    /**
     * @param int $storeId
     * @return Apache_Solr_Response
     */
    public function deleteAllDocuments($storeId)
    {
        $response = $this->getSolr($storeId)->deleteByQuery('store_id:' . $storeId);
        $this->getSolr($storeId)->commit();
        return $response;
    }

    /**
     * @param int $storeId
     * @param string[] $ids
     * @return Apache_Solr_Response
     */
    public function deleteByMultipleIds($storeId, $ids)
    {
        $response = $this->getSolr($storeId)->deleteByMultipleIds($ids);
        $this->getSolr($storeId)->commit();
        return $response;
    }
}