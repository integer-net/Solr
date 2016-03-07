<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Plain\Http;


class AutosuggestRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider dataValidGetParams
     */
    public function testCreateFromGet($params, $expectedQuery, $expectedStoreId)
    {
        $request = AutosuggestRequest::fromGet($params);
        $this->assertInstanceOf(AutosuggestRequest::class, $request);
        $this->assertEquals($expectedQuery, $request->getQuery());
        $this->assertEquals($expectedStoreId, $request->getStoreId());
    }
    public static function dataValidGetParams()
    {
        return [
            [['q' => 'this is a string', 'store_id' => '0'],
                'this is a string', 0],
            [['q' => 'this is a string', 'store_id' => '1'],
                'this is a string', 1],
            [['q' => 'this is a string', 'store_id' => '1 '],
                'this is a string', 1],
            [['q' => 'this is a string'],
                'this is a string', 0],
            [['q' => ['why not search for "Array"?'], 'store_id' => '2'],
                '', 2],
            [['q' => 'No, don\'t do it.', 'store_id' => ['1','2']],
                'No, don\'t do it.', 0],
        ];
    }
}