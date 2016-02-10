<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain;

use IntegerNet\Solr\Config\GeneralConfig;
use IntegerNet\Solr\Exception;
use IntegerNet\SolrSuggest\Implementor\AutosuggestBlock;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestResponse;
use Psr\Log\LoggerInterface;

class AutosuggestController
{
    /**
     * @var GeneralConfig
     */
    private $generalConfig;
    /**
     * @var AutosuggestBlock
     */
    private $block;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AutosuggestController constructor.
     * @param GeneralConfig $generalConfig
     * @param AutosuggestBlock $block
     * @param LoggerInterface $logger
     */
    public function __construct(GeneralConfig $generalConfig, AutosuggestBlock $block, LoggerInterface $logger)
    {
        $this->generalConfig = $generalConfig;
        $this->block = $block;
        $this->logger = $logger;
    }

    /**
     * @param AutosuggestRequest $request
     * @return AutosuggestResponse
     */
    public function process(AutosuggestRequest $request)
    {
        try {
            if (! $this->generalConfig->isActive()) {
                return new AutosuggestResponse(403, 'Forbidden: Module not active');
            }
            if ($request->getQuery() === '') {
                return new AutosuggestResponse(400, 'Bad Request: Query missing');
            }
            if ($request->getStoreId() === 0) {
                return new AutosuggestResponse(400, 'Bad Request: Store ID missing');
            }
            return new AutosuggestResponse(200, $this->block->toHtml());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return new AutosuggestResponse(500, $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return new AutosuggestResponse(500, 'Internal Server Error');
        }
    }
}