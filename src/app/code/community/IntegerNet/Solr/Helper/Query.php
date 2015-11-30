<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Milan Hacker
 */
class IntegerNet_Solr_Helper_Query extends Mage_Core_Helper_Abstract {

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