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

use IntegerNet\SolrSuggest\Plain\Entity\Attribute;

class AttributeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataConfigArray
     * @test
     * @param array $inputArray
     * @param array $expectedValues
     */
    public function shouldCreateFromConfigArray(array $inputArray, array $expectedValues)
    {
        $attribute = Attribute::fromArray($inputArray);
        $this->assertEquals($expectedValues['attribute_code'], $attribute->getAttributeCode(), 'getAttributeCode()');
        $this->assertEquals($expectedValues['label'], $attribute->getStoreLabel(), 'getStoreLabel()');
        foreach ($expectedValues['options'] as $expectedId => $expectedLabel) {
            $this->assertEquals($expectedLabel, $attribute->getSource()->getOptionText($expectedId), 'getSource()->getOptionText()');
        }
        $this->assertEquals($expectedValues['solr_boost'], $attribute->getSolrBoost(), 'getSolrBoost()');
        $this->assertEquals('varchar', $attribute->getBackendType(), 'getBackendType() should always return varchar');
        $this->assertEquals(true, $attribute->getIsSearchable(), 'getIsSearchable() should always return true');
        $this->assertEquals($expectedValues['used_for_sortby'], $attribute->getUsedForSortBy(), 'getUsedForSortBy()');
        foreach ($expectedValues['custom_data'] as $expectedKey => $expectedValue) {
            $this->assertEquals($expectedValue, $attribute->getCustomData($expectedKey), 'getCustomData('.$expectedKey.')');
        }
    }
    public static function dataConfigArray()
    {
        return [
            'all_properties' => [[
                'attribute_code' => 'color',
                'label' => 'Color',
                'options' => [90 => 'red', 91 => 'blue'],
                'images' => [90 => 'red.jpg', 91 => 'blue.jpg'],
                'solr_boost' => 1.5,
                'used_for_sortby' => true,
            ], [
                'attribute_code' => 'color',
                'label' => 'Color',
                'options' => [90 => 'red', 91 => 'blue'],
                'solr_boost' => 1.5,
                'used_for_sortby' => true,
                'custom_data' => ['images' => [90 => 'red.jpg', 91 => 'blue.jpg']]
            ]],

            'only_required_properties' => [[
                'attribute_code' => 'color',
                'label' => 'Color',
            ], [
                'attribute_code' => 'color',
                'label' => 'Color',
                'options' => [],
                'solr_boost' => null,
                'used_for_sortby' => false,
                'custom_data' => []
            ]]
        ];
    }
}