<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class IntegerNet_Solr_Config_Indexing
{
    /**
     * @var int
     */
    private $pagesize;
    /**
     * @var bool
     */
    private $deleteDocumentsBeforeIndexing;
    /**
     * @var bool
     */
    private $swapCores;

    /**
     * @param int $pagesize
     * @param bool $deleteDocumentsBeforeIndexing
     * @param bool $swapCores
     * @param string $swapCore
     */
    public function __construct($pagesize, $deleteDocumentsBeforeIndexing, $swapCores)
    {
        $this->pagesize = $pagesize;
        $this->deleteDocumentsBeforeIndexing = $deleteDocumentsBeforeIndexing;
        $this->swapCores = $swapCores;
    }

    /**
     * @return int
     */
    public function getPagesize()
    {
        return $this->pagesize;
    }

    /**
     * @return boolean
     */
    public function isDeleteDocumentsBeforeIndexing()
    {
        return $this->deleteDocumentsBeforeIndexing;
    }

    /**
     * @return boolean
     */
    public function isSwapCores()
    {
        return $this->swapCores;
    }

}