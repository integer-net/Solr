<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class IntegerNet_Solr_Config_General
{
    /**
     * @var bool
     */
    private $active;
    /**
     * @var string
     */
    private $licenseKey;
    /**
     * @var bool
     */
    private $log;
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param bool $active
     */
    public function __construct($active, $licenseKey, $log, $debug)
    {
        $this->active = $active;
        $this->licenseKey = $licenseKey;
        $this->log = $log;
        $this->debug = $debug;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getLicenseKey()
    {
        return $this->licenseKey;
    }

    /**
     * @return boolean
     */
    public function isLog()
    {
        return $this->log;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

}