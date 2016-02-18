<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * @loadFixture config
 */
class IntegerNet_Solr_Test_Model_ProductIterator extends EcomDev_PHPUnit_Test_Case_Controller
{
    /**
     * @test
     * @loadFixture catalog
     * @dataProvider dataIteratorParameters
     * @singleton customer/session
     */
    public function shouldLazyloadCollections($idFilter, $pageSize, $expectedProductIds)
    {
        // Magento needs a customer session to work with product collections :-/
        // and replacing it with a mock causes side effects with other tests :-(
        // these lines above accidentally have the same amount of characters :-)
        $this->customerSession(0);
        $iterator = new IntegerNet_Solr_Model_Bridge_LazyProductIterator(1, $idFilter, $pageSize);
        $actualProductIds = [];
        $guard = 0;
        foreach ($iterator as $product)
        {
            $actualProductIds[]= $product->getId();
            if (++$guard > 2 * count($expectedProductIds)) {
                $this->fail('Too many iterations. Collected product ids: ' . join(',', $actualProductIds));
                break;
            }
        }
        $this->assertEquals($expectedProductIds, $actualProductIds, 'product ids', 0.0, 10, false, true);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function dataIteratorParameters()
    {
        return [
            'no_filter_pagesize_1' => [null, 1, [1, 2, 3, 21001, 22101, 22111, 22201]],
            'no_filter_pagesize_3' => [null, 3, [1, 2, 3, 21001, 22101, 22111, 22201]],
            'no_filter_pagesize_4' => [null, 4, [1, 2, 3, 21001, 22101, 22111, 22201]],
            'no_filter_pagesize_5' => [null, 5, [1, 2, 3, 21001, 22101, 22111, 22201]],
            'filter_pagesize_1' => [[21000, 21001, 22101], 1, [21001, 22101]],
        ];
    }
}