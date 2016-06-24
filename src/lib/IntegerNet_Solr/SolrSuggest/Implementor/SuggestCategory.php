<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Implementor;

interface SuggestCategory
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $separator
     * @return string
     */
    public function getPath($separator);
}