<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Factory;

use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Config\StoreConfig;
use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class LoggerFactory
{
    /**
     * @var GeneralConfig
     */
    private $generalConfig;
    /**
     * @var StoreConfig
     */
    private $storeConfig;

    /**
     * LoggerFactory constructor.
     * @param GeneralConfig $generalConfig
     * @param StoreConfig $storeConfig
     */
    public function __construct(GeneralConfig $generalConfig, StoreConfig $storeConfig)
    {
        $this->generalConfig = $generalConfig;
        $this->storeConfig = $storeConfig;
    }

    public function getLogger($filename)
    {
        if ($this->generalConfig->isLog()) {
            $logger = new Logger($this->storeConfig->getLogDir(), LogLevel::DEBUG, array('filename' => $filename));
            return $logger;
        } else {
            $logger = new NullLogger();
            return $logger;
        }
    }
}