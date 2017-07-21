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

use IntegerNet\Solr\Implementor\HasUserQuery;

final class AutosuggestRequest implements HasUserQuery
{
    /**
     * @var string
     */
    private $query;
    /**
     * @var int
     */
    private $storeId;

    /**
     * AutosuggestRequest constructor.
     * @param string $query
     * @param int $storeId
     */
    public function __construct($query, $storeId)
    {
        $this->query = $query;
        $this->storeId = $storeId;
    }

    /**
     * @param array $params
     * @return AutosuggestRequest
     */
    public static function fromGet(array $params)
    {
        $query = isset($params['q']) && is_string($params['q']) ? (string) $params['q'] : '';
        $storeId = isset($params['store_id']) && is_string($params['store_id']) ? (int) $params['store_id'] : 0;
        return new self($query, $storeId);
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Returns query as entered by user
     *
     * @return string
     */
    public function getUserQueryText()
    {
        return $this->getQuery();
    }

}