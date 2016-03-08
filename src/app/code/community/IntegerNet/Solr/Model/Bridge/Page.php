<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\SolrCms\Implementor\Page;

class IntegerNet_Solr_Model_Bridge_Page implements Page
{
    /**
     * @var Mage_Cms_Model_Page
     */
    protected $_page;

    /**
     * @param Mage_Cms_Model_Page $_page
     */
    public function __construct(Mage_Cms_Model_Page $_page)
    {
        $this->_page = $_page;
    }

    /**
     * @return Mage_Cms_Model_Page
     */
    public function getMagentoPage()
    {
        return $this->_page;
    }


    public function getId()
    {
        return $this->_page->getId();
    }

    public function getStoreId()
    {
        return $this->_page->getStoreId();
    }

    public function getSolrBoost()
    {
        return $this->_page->getData('solr_boost');
    }
    
    public function getTitle()
    {
        return $this->_page->getData('title');
    }
    
    public function getContent()
    {
        return Mage::helper('cms')->getPageTemplateProcessor()->filter($this->_page->getData('content'));
    }

    public function getUrl()
    {
        return Mage::helper('cms/page')->getPageUrl($this->getId());
    }

    /**
     * @return int
     */
    public function getSolrId()
    {
        return 'page_' . $this->getId() . '_' . $this->getStoreId();
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isIndexable($storeId)
    {
        Mage::dispatchEvent('integernet_solr_can_index_page', array('page' => $this->_page));

        if ($this->_page->getSolrExclude()) {
            return false;
        }
        
        if (!$this->_page->getIsActive()) {
            return false;
        }
        
        return true;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @deprecated only use interface methods!
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_page, $method), $args);
    }
}