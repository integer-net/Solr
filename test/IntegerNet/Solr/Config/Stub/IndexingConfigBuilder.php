<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Config\Stub;

use IntegerNet\Solr\Config\IndexingConfig;

class IndexingConfigBuilder
{
    /*
     * Default values
     */
    private $pagesize = 1000,
        $deleteDocumentsBeforeIndexing = true,
        $swapCores = false;

    private function __construct()
    {
    }
    public static function defaultConfig()
    {
        return new static;
    }
    public static function swapCoreConfig()
    {
        return self::defaultConfig()->withSwapCores(true);
    }

    /**
     * @param int $pagesize
     * @return IntegerNet_Solr_Config_Indexing_Builder
     */
    public function withPagesize($pagesize)
    {
        $this->pagesize = $pagesize;
        return $this;
    }

    /**
     * @param boolean $deleteDocumentsBeforeIndexing
     * @return IntegerNet_Solr_Config_Indexing_Builder
     */
    public function withDeleteDocumentsBeforeIndexing($deleteDocumentsBeforeIndexing)
    {
        $this->deleteDocumentsBeforeIndexing = $deleteDocumentsBeforeIndexing;
        return $this;
    }

    /**
     * @param boolean $swapCores
     * @return IntegerNet_Solr_Config_Indexing_Builder
     */
    public function withSwapCores($swapCores)
    {
        $this->swapCores = $swapCores;
        return $this;
    }

    public function build()
    {
        return new IndexingConfig($this->pagesize, $this->deleteDocumentsBeforeIndexing, $this->swapCores);
    }
}