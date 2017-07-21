<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Resource;

class HttpTransportMethod
{
    const HTTP_TRANSPORT_METHOD_FILEGETCONTENTS = 'filegetcontents';
    const HTTP_TRANSPORT_METHOD_CURL = 'curl';
}