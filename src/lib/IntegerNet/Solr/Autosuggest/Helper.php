<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

/**
 * This class is a low weight replacement for the "Mage_Core_Model_Store" class in autosuggest calls
 *
 * Class IntegerNet_Solr_Autosuggest_Helper
 */
final class IntegerNet_Solr_Autosuggest_Helper
{
    protected $_query;

    public function getQuery()
    {
        if (is_null($this->_query)) {
            $this->_query = new IntegerNet_Solr_Autosuggest_Query();
        }

        return $this->_query;
    }
    
    /**
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInSearchAttributes()
    {
        $attributes = array();
        foreach((array)Mage::getStoreConfig('attribute') as $attributeCode => $attributeConfig) {
            $attributes[$attributeCode] = new IntegerNet_Solr_Autosuggest_Attribute($attributeConfig);
        }
        
        return $attributes;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @return string
     * @todo adjust
     */
    public function getFieldName($attribute)
    {
        if ($attribute->getUsedForSortBy()) {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f';

                case 'text':
                    return $attribute->getAttributeCode() . '_t';

                default:
                    return $attribute->getAttributeCode() . '_s';
            }
        } else {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f_mv';

                case 'text':
                    return $attribute->getAttributeCode() . '_t_mv';

                default:
                    return $attribute->getAttributeCode() . '_s_mv';
            }
        }
    }

    public function getQueryText()
    {
        return $_GET['q'];
    }

    /**
     * Replacement for original translation function
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $text = array_shift($args);
        return vsprintf($text, $args);
    }

    public function isCategoryPage()
    {
        return false;
    }
}