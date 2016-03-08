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

interface StoreEmulation
{
    /**
     * Starts environment emulation for given store. Previously emulated environments are stopped before new emulation starts.
     *
     * @param int $storeId
     * @return void
     */
    public function start($storeId);

    /**
     * Stops any active store emulation
     *
     * @return void
     */
    public function stop();
}