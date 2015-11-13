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
    public function getFilterableAttributes()
    {
        return $this->getFilterableInSearchAttributes();
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
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getSearchableAttributes()
    {
        $attributes = array();
        foreach((array)Mage::getStoreConfig('searchable_attribute') as $attributeCode => $attributeConfig) {
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
        switch ($attribute->getBackendType()) {
            case 'decimal':
                return $attribute->getAttributeCode() . '_f';

            case 'text':
                return $attribute->getAttributeCode() . '_t';

            default:
                return $attribute->getAttributeCode() . '_t';
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

    /**
     * Quote and escape search strings
     *
     * @param string $string String to escape
     * @return string The escaped/quoted string
     */
    public function escape ($string)
    {
        if (!is_numeric($string)) {
            if (preg_match('/\W/', $string) == 1) {
                // multiple words

                $stringLength = strlen($string);
                if ($string{0} == '"' && $string{$stringLength - 1} == '"') {
                    // phrase
                    $string = trim($string, '"');
                    $string = $this->escapePhrase($string);
                } else {
                    $string = $this->escapeSpecialCharacters($string);
                }
            } else {
                $string = $this->escapeSpecialCharacters($string);
            }
        }

        return $string;
    }

    /**
     * Escapes characters with special meanings in Lucene query syntax.
     *
     * @param string $value Unescaped - "dirty" - string
     * @return string Escaped - "clean" - string
     */
    public function escapeSpecialCharacters ($value)
    {
        // list taken from http://lucene.apache.org/core/4_4_0/queryparser/org/apache/lucene/queryparser/classic/package-summary.html#package_description
        // which mentions: + - && || ! ( ) { } [ ] ^ " ~ * ? : \ /
        // of which we escape: ( ) { } [ ] ^ " ~ : \ /
        // and explicitly don't escape: + - && || ! * ?
        $pattern = '/(\\(|\\)|\\{|\\}|\\[|\\]|\\^|"|~|\:|\\\\|\\/)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * Escapes a value meant to be contained in a phrase with characters with
     * special meanings in Lucene query syntax.
     *
     * @param string $value Unescaped - "dirty" - string
     * @return string Escaped - "clean" - string
     */
    public function escapePhrase ($value)
    {
        $pattern = '/("|\\\)/';
        $replace = '\\\$1';

        return '"' . preg_replace($pattern, $replace, $value) . '"';
    }
}