<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Event;

use ArrayObject;
use BadMethodCallException;

class Transport extends ArrayObject
{
    public function getData($key)
    {
        return $this[$key];
    }
    public function addData(array $data)
    {
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }
        return $this;
    }
    public function setData($key, $value)
    {
        $this[$key] = $value;
        return $this;
    }
    public function __call($method, $args)
    {
        $prefix = substr($method, 0, 3);
        if ($prefix === 'get') {
            return $this->getData($this->_underscore(substr($method, 3)));
        } elseif ($prefix === 'set') {
            return $this->setData($this->_underscore(substr($method, 3)), $args[0]);
        }
        throw new BadMethodCallException;
    }

    protected function _underscore($name)
    {
        return strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
    }

    protected function _camelize($name)
    {
        return uc_words($name, '');
    }
}