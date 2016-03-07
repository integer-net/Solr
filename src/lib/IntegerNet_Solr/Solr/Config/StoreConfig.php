<?php
namespace IntegerNet\Solr\Config;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class StoreConfig
{
    /**
     * @var string
     */
    private $baseUrl;
    /**
     * @var string
     */
    private $logDir;

    /**
     * @param string $baseUrl
     * @param $logDir
     */
    public function __construct($baseUrl, $logDir)
    {
        $this->baseUrl = $baseUrl;
        $this->logDir = $logDir;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return $this->logDir;
    }


}