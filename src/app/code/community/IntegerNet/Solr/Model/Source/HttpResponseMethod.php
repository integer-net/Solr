<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Milan Hacker
 */

class IntegerNet_Solr_Model_Source_HttpResponseMethod
{
    const HTTP_RESPONSE_METHOD_GET  = 'GET';
    const HTTP_RESPONSE_METHOD_POST = 'POST';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::HTTP_RESPONSE_METHOD_GET,
                'label' => Mage::helper('integernet_solr')->__(self::HTTP_RESPONSE_METHOD_GET)
            ),
            array(
                'value' => self::HTTP_RESPONSE_METHOD_POST,
                'label' => Mage::helper('integernet_solr')->__(self::HTTP_RESPONSE_METHOD_POST)
            ),
        );
    }
}