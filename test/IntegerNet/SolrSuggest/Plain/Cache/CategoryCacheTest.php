<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache;

use IntegerNet\SolrSuggest\Implementor\SuggestCategoryRepository;
use IntegerNet\SolrSuggest\Plain\Bridge\Category;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CategoryCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryCache
     */
    private $categoryCache;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SuggestCategoryRepository
     */
    private $categoryRepositoryStub;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Cache
     */
    private $cacheMock;
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        $this->cacheMock = $this->getMockForAbstractClass(Cache::class);
        $this->categoryRepositoryStub = $this->getMockForAbstractClass(SuggestCategoryRepository::class);
        $this->categoryCache = new CategoryCache($this->cacheMock, $this->categoryRepositoryStub);
    }
    /**
     * @test
     * @dataProvider dataStoreIds
     * @param int $storeId
     */
    public function shouldStoreCategories($storeId)
    {
        $categoryCacheKey = "store_{$storeId}.categories";
        $dataCategoryArray = [new Category(1, 'Books', 'books.html'), new Category(2, 'DVDs', 'dvds.html')];

        $this->categoryRepositoryStub->expects($this->any())
            ->method('findActiveCategories')
            ->with($storeId)
            ->willReturn($dataCategoryArray);

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with($categoryCacheKey, $dataCategoryArray);

        $this->categoryCache->writeCategoryCache($storeId);
    }

    public static function dataStoreIds()
    {
        return [
            [1],
            [2],
        ];
    }
}