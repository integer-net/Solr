<?php
use IntegerNet\Solr\Implementor\Attribute;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    protected function setUp()
    {
        parent::setUp();

        $colorAttribute = Mage::getResourceModel('catalog/eav_attribute')
            ->setEntityTypeId(Mage_Catalog_Model_Product::ENTITY)
            ->load('color', 'attribute_code');
        $colorAttribute->setIsFilterableInSearch(true)->save();
    }

    /**
     * @test
     * @helper integernet_solr
     */
    public function shouldGetFilterableInCatalogAttributes()
    {
        $helper = Mage::helper('integernet_solr');
        $actualAttributes = $helper->getFilterableInCatalogAttributes();
        $this->assertInternalType('array', $actualAttributes);
        $this->assertNotEmpty($actualAttributes);
        foreach ($actualAttributes as $actualAttribute) {
            $this->assertInstanceOf(Attribute::class, $actualAttribute);
        }
    }

    /**
     * @test
     * @helper integernet_solr
     */
    public function shouldGetFilterableInSearchAttributes()
    {
        $helper = Mage::helper('integernet_solr');
        $actualAttributes = $helper->getFilterableInSearchAttributes();
        $this->assertInternalType('array', $actualAttributes);
        $this->assertNotEmpty($actualAttributes);
        foreach ($actualAttributes as $actualAttribute) {
            $this->assertInstanceOf(Attribute::class, $actualAttribute);
        }
    }
    /**
     * @test
     * @helper integernet_solr
     */
    public function shouldGetFilterableAttributes()
    {
        $helper = Mage::helper('integernet_solr');
        $actualAttributes = $helper->getFilterableInCatalogOrSearchAttributes();
        $this->assertInternalType('array', $actualAttributes);
        $this->assertNotEmpty($actualAttributes);
        foreach ($actualAttributes as $actualAttribute) {
            //$this->assertInstanceOf(Attribute::class, $actualAttribute);
            // still needs to be actual attribute for indexer
            $this->assertInstanceOf(Mage_Eav_Model_Entity_Attribute::class, $actualAttribute);
        }
    }
}