<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\Solr\Indexer;

use IntegerNet\Solr\Resource\ResourceFacade;

/**
 * Collects index documents to be submitted to Solr as batches
 */
class IndexDocumentQueue
{
    /** @var  ResourceFacade */
    private $resource;
    /**
     * @var int
     */
    private $storeId;
    /**
     * @var IndexDocument[]
     */
    private $documents = array();

    /**
     * @param ResourceFacade $resource
     * @param int $storeId
     */
    public function __construct(ResourceFacade $resource, $storeId)
    {
        $this->resource = $resource;
        $this->storeId = $storeId;
    }

    /**
     * Add document to queue
     *
     * @param IndexDocument $document
     * @return void
     */
    public function add(IndexDocument $document)
    {
        $this->documents[] = $document;
    }

    /**
     * Commit previously added documents to Solr and clear queue
     *
     * @return void
     */
    public function flush()
    {
        if (!empty($this->documents)) {
            $this->resource->addDocuments($this->storeId, $this->documents);
            $this->clearDocuments();
        }
    }

    private function clearDocuments()
    {
        $this->documents = [];
    }

}