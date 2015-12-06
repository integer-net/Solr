<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Resource;

use IntegerNet\Solr\Resource\ServiceBase;
use Apache_Solr_InvalidArgumentException;

class Service extends ServiceBase
{
    /**
     * Simple Suggest interface
     *
     * @deprecated not used anymore
     * @param string $query The raw query string
     * @param int $offset The starting offset for result documents
     * @param int $limit The maximum number of result documents to return
     * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
     * @param string $method The HTTP method (Service::METHOD_GET or Service::METHOD::POST)
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     * @throws Apache_Solr_InvalidArgumentException If an invalid HTTP method is used
     */
    public function suggest($query, $offset = 0, $limit = 10, $params = array(), $method = self::METHOD_GET)
    {
        // ensure params is an array
        if (!is_null($params))
        {
            if (!is_array($params))
            {
                // params was specified but was not an array - invalid
                throw new Apache_Solr_InvalidArgumentException("\$params must be a valid array or null");
            }
        }
        else
        {
            $params = array();
        }

        // construct our full parameters

        // common parameters in this interface
        $params['wt'] = self::SOLR_WRITER;
        $params['json.nl'] = $this->_namedListTreatment;

        $params['q'] = $query;
        $params['start'] = $offset;
        $params['rows'] = $limit;

        $queryString = $this->_generateQueryString($params);

        if ($method == self::METHOD_GET)
        {
            return $this->_sendRawGet($this->_suggestUrl . $this->_queryDelimiter . $queryString);
        }
        else if ($method == self::METHOD_POST)
        {
            return $this->_sendRawPost($this->_suggestUrl, $queryString, FALSE, 'application/x-www-form-urlencoded; charset=UTF-8');
        }
        else
        {
            throw new Apache_Solr_InvalidArgumentException("Unsupported method '$method', please use the Service::METHOD_* constants");
        }
    }

}