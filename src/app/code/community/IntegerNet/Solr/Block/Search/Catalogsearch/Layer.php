<?php
/**
 * Created by PhpStorm.
 * User: mhacker
 * Date: 20.10.15
 * Time: 10:24
 */ 
class IntegerNet_Solr_Block_Search_Catalogsearch_Layer extends Enterprise_Search_Block_Catalogsearch_Layer {

    /**
     * @return bool
     */
    public function canShowBlock () {
        $solrResult = Mage::getModel('integernet_solr/result')->getSolrResult();
        return (sizeof($solrResult->response->docs) > 0) ? true : false;
    }
}