<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain;

use IntegerNet\Solr\Implementor\Factory as FactoryInterface;
use IntegerNet\SolrSuggest\Implementor\Factory as SuggestFactoryInterface;
use IntegerNet\Solr\Resource\ResourceFacade;
use IntegerNet\SolrSuggest\Plain\Bridge\AttributeRepository;
use IntegerNet\SolrSuggest\Plain\Bridge\Logger;
use IntegerNet\SolrSuggest\Plain\Bridge\NullEventDispatcher;
use IntegerNet\SolrSuggest\Plain\Bridge\SearchUrl;
use IntegerNet\SolrSuggest\Plain\Http\AutosuggestRequest;
use IntegerNet\SolrSuggest\Result\AutosuggestResult;
use IntegerNet\SolrSuggest\Request\AutosuggestRequestFactory;
use IntegerNet\SolrSuggest\Request\SearchTermSuggestRequestFactory;
use IntegerNet\SolrSuggest\Util\HtmlStringHighlighter;
use IntegerNet\SolrSuggest\Plain\Bridge\CategoryRepository;
use IntegerNet_Solr_Model_Config_Store;

final class Factory implements FactoryInterface, SuggestFactoryInterface
{
    /**
     * @var AutosuggestRequest
     */
    private $request;

    /**
     * Factory constructor.
     * @param AutosuggestRequest $request
     */
    public function __construct(AutosuggestRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Returns new configured Solr recource
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $storeConfig = array(
            $this->request->getStoreId() => new IntegerNet_Solr_Model_Config_Store($this->request->getStoreId())
        );

        return new ResourceFacade($storeConfig);
    }

    /**
     * Returns new Solr result wrapper
     *
     * @return \IntegerNet\Solr\Request\Request
     */
    public function getSolrRequest($requestMode = self::REQUEST_MODE_AUTODETECT)
    {
        $storeConfig = $this->getStoreConfig($this->request->getStoreId());
        if ($storeConfig->getGeneralConfig()->isLog()) {
            $logger = new Logger();
            $logger->setFile(
                $requestMode === self::REQUEST_MODE_SEARCHTERM_SUGGEST ? 'solr_suggest.log' : 'solr.log'
            );
        } else {
            $logger = new \Psr\Log\NullLogger();
        }
        $applicationContext = new \IntegerNet\Solr\Request\ApplicationContext(
            new AttributeRepository(), $storeConfig->getResultsConfig(), $storeConfig->getAutosuggestConfig(), new NullEventDispatcher(), $logger
        );
        switch ($requestMode) {
            case self::REQUEST_MODE_SEARCHTERM_SUGGEST:
                $applicationContext->setQuery($this->request);
                $factory = new SearchTermSuggestRequestFactory($applicationContext, $this->getSolrResource(), $this->request->getStoreId());
                break;
            default:
            case self::REQUEST_MODE_AUTOSUGGEST:
                $applicationContext
                    ->setFuzzyConfig($storeConfig->getFuzzyAutosuggestConfig())
                    ->setQuery($this->request);
                $factory = new AutosuggestRequestFactory($applicationContext, $this->getSolrResource(), $this->request->getStoreId());
        }
        return $factory->createRequest();
    }

    /**
     * @return \IntegerNet\SolrSuggest\Result\AutosuggestResult
     */
    public function getAutosuggestResult()
    {
        $storeConfig = $this->getStoreConfig($this->request->getStoreId());
        return new AutosuggestResult(
            $this->request->getStoreId(),
            $storeConfig->getGeneralConfig(),
            $storeConfig->getAutosuggestConfig(),
            $this->request,
            new SearchUrl(),
            new CategoryRepository(),
            new AttributeRepository(),
            $this->getSolrRequest(self::REQUEST_MODE_AUTOSUGGEST),
            $this->getSolrRequest(self::REQUEST_MODE_SEARCHTERM_SUGGEST),
            new HtmlStringHighlighter()
        );
    }

    /**
     * @param int $storeId
     * @return IntegerNet_Solr_Model_Config_Store
     */
    public function getStoreConfig($storeId)
    {
        $storeConfig = new IntegerNet_Solr_Model_Config_Store($storeId);
        return $storeConfig;
    }


}