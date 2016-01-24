<?php
use IntegerNet\Solr\Resource\ResourceFacade;

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Suggestion
{

    /** @var null|Apache_Solr_Response */
    protected $_solrSuggestion = null;

    /**
     * @return Apache_Solr_Response
     */
    public function getSolrSuggestion()
    {
        if (is_null($this->_solrSuggestion)) {
            $this->_solrSuggestion = Mage::helper('integernet_solr/factory')->getSolrRequest(true)->doRequest();
        }

        return $this->_solrSuggestion;
    }
}