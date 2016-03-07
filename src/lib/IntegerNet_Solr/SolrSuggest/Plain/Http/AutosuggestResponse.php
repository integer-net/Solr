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

final class AutosuggestResponse
{
    /**
     * @var int
     */
    private $status;
    /**
     * @var string
     */
    private $body;

    /**
     * AutosuggestResponse constructor.
     * @param int $status
     * @param string $body
     */
    public function __construct($status, $body)
    {
        $this->status = $status;
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

}