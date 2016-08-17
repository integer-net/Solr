<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

use IntegerNet\Solr\Implementor\SolrRequestFactory;

class IntegerNet_SolrPro_Model_Categories_Result
{
    /**
     * @var $_solrRequest \IntegerNet\Solr\Request\Request
     */
    protected $_solrRequest;
    /**
     * @var $_solrResult null|\IntegerNet\Solr\Resource\SolrResponse
     */
    protected $_solrResult = null;

    function __construct()
    {
        $this->_solrRequest = Mage::helper('integernet_solrpro')->factory()->getSolrRequest(SolrRequestFactory::REQUEST_MODE_CATEGORY_SEARCH);
    }

    /**
     * Call Solr server twice: Once without fuzzy search, once with (if configured)
     *
     * @return \IntegerNet\Solr\Resource\SolrResponse
     */
    public function getSolrResult()
    {
        if (is_null($this->_solrResult)) {
            $this->_solrResult = $this->_solrRequest->doRequest();
        }

        return $this->_solrResult;
    }
}