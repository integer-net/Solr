<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Config\Stub;

use IntegerNet\Solr\Config\GeneralConfig;

class GeneralConfigBuilder
{
    /*
     * Default values
     */
    private $active = true,
        $licenseKey = '',
        $log = true,
        $debug = false;

    private function __construct()
    {
    }

    public static function defaultConfig()
    {
        return new static;
    }

    /**
     * @param boolean $active
     * @return GeneralConfigBuilder
     */
    public function withActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param string $licenseKey
     * @return GeneralConfigBuilder
     */
    public function withLicenseKey($licenseKey)
    {
        $this->licenseKey = $licenseKey;
        return $this;
    }

    /**
     * @param boolean $log
     * @return GeneralConfigBuilder
     */
    public function withLog($log)
    {
        $this->log = $log;
        return $this;
    }

    /**
     * @param boolean $debug
     * @return GeneralConfigBuilder
     */
    public function withDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    public function build()
    {
        return new GeneralConfig(
            $this->active, $this->licenseKey, $this->log, $this->debug
        );
    }
}