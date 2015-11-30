<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;

final class SearchString
{
    /**
     * @var $rawString string
     */
    private $rawString;
    /**
     * @var $escapedString string
     */
    private $escapedString;

    public function __construct($rawString)
    {
        $this->rawString = $rawString;
    }

    public function getRawString()
    {
        return $this->rawString;
    }

    public function getEscapedString()
    {
        if ($this->escapedString === null) {
            $this->escapedString = $this->escape($this->getRawString());
        }
        return $this->escapedString;
    }


    /**
     * Quote and escape search strings
     *
     * @param string $string String to escape
     * @return string The escaped/quoted string
     */
    public static function escape ($string)
    {
        if (!is_numeric($string)) {
            if (preg_match('/\W/', $string) == 1) {
                // multiple words

                $stringLength = strlen($string);
                if ($string{0} == '"' && $string{$stringLength - 1} == '"') {
                    // phrase
                    $string = trim($string, '"');
                    $string = self::escapePhrase($string);
                } else {
                    $string = self::escapeSpecialCharacters($string);
                }
            } else {
                $string = self::escapeSpecialCharacters($string);
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
    private static function escapeSpecialCharacters ($value)
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
    private static function escapePhrase ($value)
    {
        $pattern = '/("|\\\)/';
        $replace = '\\\$1';

        return '"' . preg_replace($pattern, $replace, $value) . '"';
    }
}