<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Milan Hacker
 */
class IntegerNet_Solr_Model_Ping
{
    /**
     * @var null|bool
     */
    protected $_testPing = null;

    /**
     * @var IntegerNet_Solr_Model_Ping|null
     */
    protected static $_instance = null;

    public function __construct()
    {
        if (self::$_instance == null) {
            $this->setPing();
            self::$_instance = $this;
        }
        return self::$_instance;
    }

    protected function setPing()
    {
        $solr = Mage::getResourceModel('integernet_solr/solr')->getSolrService(
            Mage::app()->getStore()->getId()
        );
        $this->_testPing = boolval($solr->ping());
    }

    /**
     * @return bool|float|null
     */
    public function getPing()
    {
        if ($this->_testPing == null) {
            $this->setPing();
        }
        return $this->_testPing;
    }
}