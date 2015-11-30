<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;
use PHPUnit_Framework_TestCase;

class SearchStringTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider dataEscapedStrings
     * @param $inputString
     * @param $expectedEscapedString
     */
    public function shouldEscapeStrings($inputString, $expectedEscapedString)
    {
        $searchString = new SearchString($inputString);
        $this->assertEquals($expectedEscapedString, $searchString->getEscapedString(), 'getEscapedString() should return escaped string');
        $this->assertEquals($inputString, $searchString->getRawString(), 'getRawString() should return unmodified input');
    }
    public static function dataEscapedStrings()
    {
        // list taken from http://lucene.apache.org/core/4_4_0/queryparser/org/apache/lucene/queryparser/classic/package-summary.html#package_description
        // which mentions: + - && || ! ( ) { } [ ] ^ " ~ * ? : \ /
        // of which we escape: ( ) { } [ ] ^ " ~ : \ /
        // and explicitly don't escape: + - && || ! * ?
        return array(
            'single_word' => ['foo', 'foo'],
            'operators' => ['foo +bar', 'foo +bar'],
            'all_special' => ['+ - && || ! ( ) { } [ ] ^ " ~ * ? : \\ /',
                '+ - && || ! \( \) \{ \} \[ \] \^ \" \~ * ? \: \\\\ \/'],
            'phrase' => ['"multiple words (quoted, can contain "quotes" or \\(escaped\\) characters)"',
                '"multiple words (quoted, can contain \\"quotes\\" or \\\\(escaped\\\\) characters)"'],
        );
    }
}