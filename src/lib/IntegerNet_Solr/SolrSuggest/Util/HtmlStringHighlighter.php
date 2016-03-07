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

final class HtmlStringHighlighter implements StringHighlighter
{
    /**
     * @param string $haystack
     * @param string $needle
     * @return string
     */
    public function highlight($haystack, $needle)
    {
        $quotedNeedle = preg_quote(trim($needle), '/');
        if (strpos($haystack, '<') === false) {
            return preg_replace('/(' . $quotedNeedle . ')/i', '<span class="highlight">$1</span>', $haystack);
        }
        return preg_replace_callback('/(' . $quotedNeedle . ')(.*?>)/i',
            array($this, 'checkOpenTag'),
            $haystack);
    }

    /**
     * @param array $matches
     * @return string
     */
    private function checkOpenTag($matches)
    {
        if (strpos($matches[0], '<') === false) {
            return $matches[0];
        } else {
            return '<span class="highlight">' . $matches[1] . '</span>' . $matches[2];
        }
    }
}