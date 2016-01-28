<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Implementor;

interface Category
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
     * @return int[]
     */
    public function getPathIds();

    /**
     * @return string
     */
    public function getName();
}