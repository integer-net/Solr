<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 
class IntegerNet_Solr_Block_CatalogSearch_Autocomplete extends Mage_Core_Block_Template 
{
    protected $_suggestData = null;

    protected function _construct()
    {
        $this->setTemplate('integernet/solr/autosuggest.phtml');
    }
    
    public function getKeywordSuggestions()
    {
        if (!$this->_suggestData) {
            $collection = Mage::getResourceModel('integernet_solr/catalogSearch_query_collection');
            $query = $this->helper('catalogsearch')->getQueryText();
            $counter = 0;
            $data = array();
            $maxNumberSearchwordSuggestions = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_searchword_suggestions'));
            
            foreach ($collection as $item) {

                if (++$counter > $maxNumberSearchwordSuggestions) {
                    break;
                }
                
                $_data = array(
                    'title' => $item->getQueryText(),
                    'row_class' => $counter%2?'odd':'even',
                    'num_of_results' => $item->getNumResults()
                );

                if ($counter == 1) {
                    $_data['row_class'] .= ' first';
                }


                if ($item->getQueryText() == $query) {
                    array_unshift($data, $_data);
                }
                else {
                    $data[] = $_data;
                }
            }
            
            $data[max(array_keys($data))]['row_class'] .= ' last';
            
            $this->_suggestData = $data;
        }
        return $this->_suggestData;
    }
}