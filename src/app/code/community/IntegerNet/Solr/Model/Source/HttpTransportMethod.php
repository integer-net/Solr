<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */ 

class IntegerNet_Solr_Model_Source_HttpTransportMethod
{
    const HTTP_TRANSPORT_METHOD_FILEGETCONTENTS = 'filegetcontents';
    const HTTP_TRANSPORT_METHOD_CURL = 'curl';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::HTTP_TRANSPORT_METHOD_FILEGETCONTENTS,
                'label' => Mage::helper('integernet_solr')->__('file_get_contents (Default)'),
            ),
            array(
                'value' => self::HTTP_TRANSPORT_METHOD_CURL,
                'label' => Mage::helper('integernet_solr')->__('cURL'),
            ),
        );
    }
}