<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Milan Hacker
 */

class IntegerNet_Solr_Model_Source_SearchField
{
    const SEARCH_FIELD_TEXT                 = 'text';
    const SEARCH_FIELD_TEXT_PLAIN           = 'text_plain';
    const SEARCH_FIELD_TEXT_AUTOCOMPLETE    = 'text_autocomplete';
    const SEARCH_FIELD_TEXT_REV             = 'text_rev';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::SEARCH_FIELD_TEXT,
                'label' => Mage::helper('integernet_solr')->__(self::SEARCH_FIELD_TEXT)
            ),
            array(
                'value' => self::SEARCH_FIELD_TEXT_PLAIN,
                'label' => Mage::helper('integernet_solr')->__(self::SEARCH_FIELD_TEXT_PLAIN)
            ),
            array(
                'value' => self::SEARCH_FIELD_TEXT_AUTOCOMPLETE,
                'label' => Mage::helper('integernet_solr')->__(self::SEARCH_FIELD_TEXT_AUTOCOMPLETE)
            ),
            array(
                'value' => self::SEARCH_FIELD_TEXT_REV,
                'label' => Mage::helper('integernet_solr')->__(self::SEARCH_FIELD_TEXT_REV)
            ),
        );
    }
}