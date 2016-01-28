<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;
use \IntegerNet\Solr\Implementor\EventDispatcher;

/**
 * This class is a low weight replacement for the "Mage_Core_Model_Store" class in autosuggest calls
 *
 * Class IntegerNet_Solr_Autosuggest_Helper
 */
final class IntegerNet_Solr_Autosuggest_Helper implements AttributeRepository, EventDispatcher
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
     * @return string[]
     */
    public function getAttributeCodesToIndex()
    {
        // not used in autosuggest
        return array();
    }

    /**
     * @return Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true)
    {
        return $this->getFilterableInSearchAttributes();
    }

    /**
     * @return Attribute[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true)
    {
        $attributes = array();
        foreach((array)Mage::getStoreConfig('attribute') as $attributeCode => $attributeConfig) {
            $attributes[$attributeCode] = new IntegerNet_Solr_Autosuggest_Attribute($attributeConfig);
        }
        
        return $attributes;
    }

    /**
     * @return Attribute[]
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
        if ($attribute->getUsedForSortBy()) {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f';

                case 'text':
                    return $attribute->getAttributeCode() . '_t';

                default:
                    return $attribute->getAttributeCode() . '_t';
            }
        } else {
            switch ($attribute->getBackendType()) {
                case 'decimal':
                    return $attribute->getAttributeCode() . '_f_mv';

                case 'text':
                    return $attribute->getAttributeCode() . '_t_mv';

                default:
                    return $attribute->getAttributeCode() . '_t_mv';
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

    /**
     * @param bool $useAlphabeticalSearch
     * @return Attribute[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true)
    {
        // not used in autosuggest
        return array();
    }

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true)
    {
        // not used in autosuggest
        return array();
    }

    /**
     * @param string $attributeCode
     * @return Attribute
     */
    public function getAttributeByCode($attributeCode)
    {
        return new IntegerNet_Solr_Autosuggest_Attribute(Mage::getStoreConfig('attribute/' . $attributeCode));;
    }

    /**
     * Dispatch event
     *
     * @param string $eventName
     * @param array $data
     * @return void
     */
    public function dispatch($eventName, array $data = array())
    {
        Mage::dispatchEvent($eventName, $data);
    }


}