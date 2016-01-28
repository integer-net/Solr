<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Factory;
use IntegerNet\SolrSuggest\Implementor\Factory as SuggestFactory;
use IntegerNet\Solr\Resource\ResourceFacade;

/**
 * This class is a low weight replacement for the factory helper class in autosuggest calls
 */
final class IntegerNet_Solr_Autosuggest_Factory implements Factory, SuggestFactory
{
    /**
     * Returns new configured Solr recource
     *
     * @return ResourceFacade
     */
    public function getSolrResource()
    {
        $store = IntegerNet_Solr_Autosuggest_Mage::app()->getStore();
        $storeConfig = array(
            $store->getId() => new IntegerNet_Solr_Model_Config_Store($store->getId())
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
        // TODO: Implement getSolrRequest() method.
        // not used as long as autosuggest lib uses its own result model
    }

    /**
     * @return \IntegerNet\SolrSuggest\Result\AutosuggestResult
     */
    public function getAutosuggestResult()
    {
        //TODO implement
        // $attributeRepository = new IntegerNet_Solr_Autosuggest_Helper()
    }


}