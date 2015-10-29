<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Milan Hacker
 */
class IntegerNet_Solr_Block_Search_Catalogsearch_Layer extends Enterprise_Search_Block_Catalogsearch_Layer {

    /**
     * @return bool
     */
    public function canShowBlock () {
        $solrResult = Mage::getSingleton('integernet_solr/result')->getSolrResult();
        return (sizeof($solrResult->response->docs) > 0) ? true : false;
    }
}