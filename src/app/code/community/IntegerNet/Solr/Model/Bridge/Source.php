<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
class IntegerNet_Solr_Model_Bridge_Source implements IntegerNet_Solr_Implementor_Source
{
    /**
     * @var Mage_Eav_Model_Entity_Attribute_Source_Interface
     */
    private $_source;

    /**
     * @param $_source Mage_Eav_Model_Entity_Attribute_Source_Interface
     */
    public function __construct(Mage_Eav_Model_Entity_Attribute_Source_Interface $_source)
    {
        $this->_source = $_source;
    }

    /**
     * @param int $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        return $this->_source->getOptionText($optionId);
    }


    /**
     * Delegate all other calls (by Magento) to source model
     *
     * @param $name string
     * @param $arguments array
     * @return mixed
     */
    function __call($name, $arguments)
    {
        return call_user_func_array([$this->_source, $name], $arguments);
    }
}