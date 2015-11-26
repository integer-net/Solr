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

interface ParamsBuilder
{
    /**
     * Return parameters as array as expected by solr service
     *
     * @param int $storeId
     * @param bool $fuzzy
     * @return mixed[]
     */
    public function buildAsArray($storeId, $fuzzy);

}