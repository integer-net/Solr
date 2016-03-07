<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Util;

class HtmlStringHighlighterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataHighlight
     */
    public function testHighlight($haystack, $needle, $expectedResult)
    {
        $highlighter = new HtmlStringHighlighter();
        $this->assertEquals($expectedResult, $highlighter->highlight($haystack, $needle));
    }
    public static function dataHighlight()
    {
        return [
            'match word' => ['Hallo Welt!', 'Welt', 'Hallo <span class="highlight">Welt</span>!'],
            'match html attribute' => ['<b class="Welt">Hallo</b>!', 'Welt', '<b class="Welt">Hallo</b>!'],
            'match after html attribute' => ['<b class="Welt">Hallo Welt</b>!', 'Welt', '<b class="Welt">Hallo <span class="highlight">Welt</span></b>!'],
            'special characters' => ['Match this: "$/()[]{}}]\\', '"$/()[]{}}]\\', 'Match this: <span class="highlight">"$/()[]{}}]\\</span>'],
        ];
    }
}