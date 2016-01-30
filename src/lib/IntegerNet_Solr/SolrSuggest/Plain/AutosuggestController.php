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
use IntegerNet\SolrSuggest\Implementor\AutosuggestBlock;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestResponse;

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
     * AutosuggestController constructor.
     * @param GeneralConfig $generalConfig
     * @param AutosuggestBlock $block
     */
    public function __construct(GeneralConfig $generalConfig, AutosuggestBlock $block)
    {
        $this->generalConfig = $generalConfig;
        $this->block = $block;
    }

    /**
     * @param AutosuggestRequest $request
     * @return AutosuggestResponse
     */
    public function process(AutosuggestRequest $request)
    {
        if (! $this->generalConfig->isActive()) {
            return new AutosuggestResponse(403, 'Forbidden: Module not active');
        }
        if ($request->getQuery() === '') {
            return new AutosuggestResponse(400, 'Bad Request: Query missing');
        }
        return new AutosuggestResponse(200, $this->block->toHtml());
    }
}