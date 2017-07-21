<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Bridge;
use IntegerNet\Solr\Config\StoreConfig;
use IntegerNet\SolrSuggest\Implementor\SearchUrl as SearchUrlInterface;

class SearchUrl implements SearchUrlInterface
{
    /**
     * @var StoreConfig
     */
    private $storeConfig;

    /**
     * SearchUrl constructor.
     * @param StoreConfig $storeConfig
     */
    public function __construct(StoreConfig $storeConfig)
    {
        $this->storeConfig = $storeConfig;
    }

    /**
     * Returns search URL for given user query text
     *
     * @param string $queryText
     * @param string[] $additionalParameters
     * @return string
     */
    public function getUrl($queryText, array $additionalParameters = array())
    {
        $route = 'catalogsearch/result';
        $params = array_merge(array('q' => $queryText), $additionalParameters);

        $url = $this->storeConfig->getBaseUrl();
        $url = str_replace('autosuggest.php', 'index.php', $url);
        $url .= $route;
        $isFirstParam = true;
        foreach($params as $key => $value) {
            if ($isFirstParam) {
                $url .= '?';
                $isFirstParam = false;
            } else {
                $url .= '&';
            }
            $url .= $key . '=' . urlencode($value);
        }

        return $url;

    }

}