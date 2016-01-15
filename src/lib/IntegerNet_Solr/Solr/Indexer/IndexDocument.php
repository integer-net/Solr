<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Indexer;

/**
 * Represents a document to be indexed by Solr
 */
class IndexDocument extends \ArrayObject
{
    public function getData($key = null)
    {
        if ($key === null) {
            return $this->getArrayCopy();
        }
        if (!isset($this[$key])) {
            return null;
        }
        return $this[$key];
    }
    public function hasData($key)
    {
        return $this->offsetExists($key);
    }
    public function setData($key, $value)
    {
        $this[$key] = $value;
        return $this;
    }
}