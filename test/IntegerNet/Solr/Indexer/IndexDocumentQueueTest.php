<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Indexer;

use IntegerNet\Solr\Resource\ResourceFacade;

class IndexDocumentQueueTest extends \PHPUnit_Framework_TestCase
{
    private static $storeId = 1;
    /**
     * @var IndexDocumentQueue
     */
    private $indexDocumentQueue;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResourceFacade
     */
    private $resourceMock;

    protected function setUp()
    {
        $this->resourceMock = $this->getMockBuilder(ResourceFacade::class)
            ->disableOriginalConstructor()
            ->setMethods(['addDocuments'])
            ->getMock();
        $this->indexDocumentQueue = new IndexDocumentQueue($this->resourceMock, self::$storeId);
    }

    /**
     * @test
     * @dataProvider dataQueue
     */
    public function documentsShouldBeAddedOnFlush($documentBatches)
    {
        $this->resourceMock->expects($this->exactly(count($documentBatches)))
            ->method('addDocuments')
            ->withConsecutive(...\array_map(function($documents) {
                return [self::$storeId, $documents];
            }, $documentBatches));
        foreach ($documentBatches as $documents) {
            foreach ($documents as $d) {
                $this->indexDocumentQueue->add($d);
            }
            $this->indexDocumentQueue->flush();
        }
    }

    /**
     * @test
     */
    public function emptyBatchShouldNotTriggerAddDocument()
    {
        $this->resourceMock->expects($this->never())->method('addDocuments');
        $this->indexDocumentQueue->flush();
    }

    public static function dataQueue()
    {
        $documentBatches = [
            [
                new IndexDocument(['key1' => 'A', 'key2' => 'Z']),
                new IndexDocument(['key1' => 'B', 'key2' => 'Y']),
            ],
            [
                new IndexDocument(['key1' => 'C', 'key2' => 'X']),
                new IndexDocument(['key1' => 'D', 'key2' => 'W']),
            ],
            [
                new IndexDocument(['key1' => 'E', 'key2' => 'V']),
            ],
        ];

        return [
            'single_batch' => [[$documentBatches[0]]],
            'multiple_batches' => [$documentBatches],
        ];
    }
}