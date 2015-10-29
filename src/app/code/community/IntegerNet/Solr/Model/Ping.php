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
    private static $_testPing = null;

    public function __construct()
    {
        if (self::$_testPing == null) {
            $this->setPing();
        }
    }

    protected function setPing()
    {
        if (Mage::getStoreConfigFlag('integernet_solr/general/is_active', Mage::app()->getStore()->getId())) {

            $solr = Mage::getResourceModel('integernet_solr/solr')->getSolrService(
                Mage::app()->getStore()->getId()
            );
            self::$_testPing = boolval($solr->ping());

        } else {
            self::$_testPing = false;
        }
    }

    /**
     * @return bool
     */
    public function getPing()
    {
        if (self::$_testPing == null) {
            $this->setPing();
        }
        return self::$_testPing;
    }
}