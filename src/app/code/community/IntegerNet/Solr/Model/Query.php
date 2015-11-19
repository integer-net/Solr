<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Model_Query
{
    protected $_isAutosuggest = false;

    /**
     * @param bool $isAutosuggest
     */
    public function __construct($isAutosuggest)
    {
        $this->_isAutosuggest = $isAutosuggest;
    }

    /**
     * Returns query as entered by user
     *
     * @return string
     */
    public function getUserQueryText()
    {
        $query = Mage::helper('catalogsearch')->getQuery();
        $queryText = $query->getQueryText();
        if ($query->getSynonymFor()) {
            $queryText = $query->getSynonymFor();
            return $queryText;
        }
        return $queryText;
    }

    /**
     * Returns query prepared for Solr
     *
     * @param $allowFuzzy
     * @param $broaden
     * @return string
     */
    public function getSolrQueryText($allowFuzzy, $broaden)
    {
        $queryText = $this->getUserQueryText();

        $transportObject = new Varien_Object(array(
            'query_text' => $queryText,
        ));

        Mage::dispatchEvent('integernet_solr_update_query_text', array('transport' => $transportObject));

        $queryText          = $transportObject->getQueryText();

        if ($this->_isAutosuggest) {
            $isFuzzyActive      = Mage::getStoreConfigFlag('integernet_solr/fuzzy/is_active_autosuggest');
            $sensitivity        = Mage::getStoreConfig('integernet_solr/fuzzy/sensitivity_autosuggest');
        } else {
            $isFuzzyActive      = Mage::getStoreConfigFlag('integernet_solr/fuzzy/is_active');
            $sensitivity        = Mage::getStoreConfig('integernet_solr/fuzzy/sensitivity');
        }

        $queryText = Mage::helper('integernet_solr/query')->escape($queryText);

        if ($allowFuzzy && $isFuzzyActive) {
            $queryText .= '~' . floatval($sensitivity);
        } else {

            $searchValue = ($broaden) ? explode(' ', $queryText) : $queryText;
            $queryText = '';

            $attributes = Mage::helper('integernet_solr')->getSearchableAttributes();
            $boost      = '';
            $isFirst    = true;

            foreach ($attributes as $attribute) {

                if ($attribute->getIsSearchable() == 1) {

                    $fieldName = Mage::helper('integernet_solr')->getFieldName($attribute);

                    if (strstr($fieldName, '_f') == false) {

                        if (Mage::getStoreConfigFlag('integernet_solr/general/debug')) {
                            $boost = '^' . floatval($attribute->getSolrBoost());
                        }

                        if ($broaden) {

                            foreach ($searchValue as $value) {
                                $queryText .= ($isFirst) ? '' : ' ';
                                $queryText .= $fieldName . ':"' . trim($value) . '"~100' . $boost;
                                $isFirst = false;
                            }

                        } else {
                            $queryText .= ($isFirst) ? '' : ' ';
                            $queryText .= $fieldName . ':"' . trim($searchValue) . '"~100' . $boost;
                            $isFirst = false;
                        }
                    }
                }
            }
        }
        return $queryText;
    }
}