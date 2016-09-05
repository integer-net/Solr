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
 * @loadFixture registry
 * @loadFixture config
 */
class IntegerNet_Solr_Test_Model_ProductIterator extends EcomDev_PHPUnit_Test_Case_Controller
{
    /**
     * @test
     * @loadFixture catalog
     * @dataProvider dataIteratorParameters
     */
    public function shouldLazyloadCollections($idFilter, $pageSize, $expectedProductIds, $expectedInnerIteratorCount)
    {
        // Magento needs a customer session to work with product collections :-/
        // and replacing it with a mock causes side effects with other tests :-(
        // these lines above accidentally have the same amount of characters :-)
        $this->customerSession(0);

        $productRepository = new IntegerNet_Solr_Model_Bridge_ProductRepository();
        $productRepository->setPageSizeForIndex($pageSize);
        
        $iterator = $productRepository->getProductsForIndex(1, $idFilter);
        $actualProductIds = [];
        $guard = 0;
        $callbackMock = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        /*$callbackMock->expects($this->exactly($expectedInnerIteratorCount))
            ->method('__invoke');*/
        $iterator->setPageCallback($callbackMock);
        foreach ($iterator as $product)
        {
            if (!in_array(intval($product->getId()), $actualProductIds)) {
                $actualProductIds[]= intval($product->getId());
            }
            if (++$guard > 2 * count($expectedProductIds)) {
                $this->fail('Too many iterations. Collected product ids: ' . join(',', $actualProductIds));
                break;
            }
        }
        $this->assertEquals($expectedProductIds, array_unique($actualProductIds), 'product ids', 0.0, 10, false, true);
        $this->assertEventDispatchedExactly('integernet_solr_product_collection_load_after', $expectedInnerIteratorCount);
    }


    /**
     * @return int[]
     */
    protected function _getAllProductIds()
    {
        /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection');
        return $productCollection->getAllIds();
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function dataIteratorParameters()
    {
        return [
            'no_filter_pagesize_1' => [null, 1, [1, 2, 3, 21001, 22101, 22111, 22201], 7],
            'no_filter_pagesize_3' => [null, 3, [1, 2, 3, 21001, 22101, 22111, 22201], 4],
            'no_filter_pagesize_4' => [null, 4, [1, 2, 3, 21001, 22101, 22111, 22201], 3],
            'no_filter_pagesize_5' => [null, 5, [1, 2, 3, 21001, 22101, 22111, 22201], 2],
            'filter_pagesize_1' => [[21000, 21001, 22101], 1, [21001, 22101], 3],
        ];
    }
}