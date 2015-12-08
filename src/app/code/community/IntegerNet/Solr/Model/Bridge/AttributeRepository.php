<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Attribute;
class IntegerNet_Solr_Model_Bridge_AttributeRepository
{
    /**
     * Holds attribute instances with their Magento attributes as attached data
     *
     * @var SplObjectStorage
     */
    protected $_attributeStorage;

    public function __construct()
    {
        $this->_attributeStorage = new SplObjectStorage();
    }

    /**
     * Creates and registers bridge object for given Magento attribute
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $magentoAttribute
     * @return IntegerNet_Solr_Model_Bridge_Attribute
     */
    public function _registerAttribute(Mage_Catalog_Model_Resource_Eav_Attribute $magentoAttribute)
    {
        $attribute = new IntegerNet_Solr_Model_Bridge_Attribute($magentoAttribute);
        $this->_attributeStorage->attach($attribute, $magentoAttribute);
        return $attribute;
    }

    /**
     * Returns Magento attribute for a given registered attribute instance
     * @param Attribute $attribute
     * @return mixed|null|object
     */
    public function getMagentoAttribute(Attribute $attribute)
    {
        if ($this->_attributeStorage->contains($attribute)) {
            return $this->_attributeStorage[$attribute];
        }
        return null;
    }
}