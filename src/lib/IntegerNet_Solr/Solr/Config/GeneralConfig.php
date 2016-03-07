<?php
namespace IntegerNet\Solr\Config;
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
final class GeneralConfig
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
     * @param bool $active
     * @param string $licenseKey
     * @param bool $log
     */
    public function __construct($active, $licenseKey, $log)
    {
        $this->active = $active;
        $this->licenseKey = $licenseKey;
        $this->log = $log;
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


}