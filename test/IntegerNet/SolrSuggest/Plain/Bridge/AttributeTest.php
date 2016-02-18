<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Bridge;

class AttributeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldCreateFromConfigArray()
    {
        $attribute = Attribute::fromArray([
            'attribute_code' => 'color',
            'label' => 'Color',
            'options' => [90 => 'red', 91 => 'blue'],
            'images' => [90 => 'red.jpg', 91 => 'blue.jpg'],
            'solr_boost' => 1.5,
            'used_for_sortby' => true,
        ]);
        $this->assertEquals('color', $attribute->getAttributeCode(), 'getAttributeCode()');
        $this->assertEquals('Color', $attribute->getStoreLabel(), 'getStoreLabel()');
        $this->assertEquals('red', $attribute->getSource()->getOptionText(90), 'getSource()->getOptionText()');
        $this->assertEquals('blue', $attribute->getSource()->getOptionText(91), 'getSource()->getOptionText()');
        $this->assertEquals(1.5, $attribute->getSolrBoost(), 'getSolrBoost()');
        $this->assertEquals('varchar', $attribute->getBackendType(), 'getBackendType() should always return varchar');
        $this->assertEquals(true, $attribute->getIsSearchable(), 'getIsSearchable() should always return varchar');
        $this->assertEquals(true, $attribute->getUsedForSortBy(), 'getUsedForSortBy()');
        $this->assertEquals([90 => 'red.jpg', 91 => 'blue.jpg'], $attribute->getCustomData('images'), 'getCustomData(images)');
    }
}