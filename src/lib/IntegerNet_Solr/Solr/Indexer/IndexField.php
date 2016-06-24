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

use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\EventDispatcher;

class IndexField
{
    /**
     * @var Attribute
     */
    private $attribute;
    /**
     * @var boolean
     */
    private $forSorting;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param Attribute $attribute
     * @param EventDispatcher $eventDispatcher
     * @param boolean $forSorting
     */
    public function __construct(Attribute $attribute, EventDispatcher $eventDispatcher, $forSorting = false)
    {
        $this->attribute = $attribute;
        $this->forSorting = $forSorting;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function forSorting($forSorting = true)
    {
        return new self($this->attribute, $this->eventDispatcher, $forSorting);
    }

    public function getFieldName()
    {
        $transportObject = new Transport(array(
            'fieldname' => '',
        ));

        $this->getEventDispatcher()->dispatch('integernet_solr_get_fieldname', array(
            'attribute' => $this->attribute, 
            'transport' => $transportObject
        ));
        
        if ($fieldName = $transportObject->getData('fieldname')) {
            return $fieldName;
        }

        if ($this->attribute->getUsedForSortBy() || $this->forSorting) {
            switch ($this->attribute->getBackendType()) {
                case Attribute::BACKEND_TYPE_DECIMAL:
                    return $this->attribute->getAttributeCode() . '_f';

                case Attribute::BACKEND_TYPE_TEXT:
                    return $this->attribute->getAttributeCode() . '_t';

                case Attribute::BACKEND_TYPE_INT:
                    if ($this->attribute->getFacetType() !== Attribute::FACET_TYPE_SELECT) {
                        return $this->attribute->getAttributeCode() . '_i';
                    }
                    // fallthrough intended
                case Attribute::BACKEND_TYPE_VARCHAR:
                default:
                    return ($this->forSorting) ? $this->attribute->getAttributeCode() . '_s' : $this->attribute->getAttributeCode() . '_t';
            }
        } else {
            switch ($this->attribute->getBackendType()) {
                case Attribute::BACKEND_TYPE_DECIMAL:
                    return $this->attribute->getAttributeCode() . '_f_mv';

                case Attribute::BACKEND_TYPE_TEXT:
                    return $this->attribute->getAttributeCode() . '_t_mv';

                case Attribute::BACKEND_TYPE_INT:
                    if ($this->attribute->getFacetType() != Attribute::FACET_TYPE_SELECT) {
                        return $this->attribute->getAttributeCode() . '_i_mv';
                    }
                // fallthrough intended
                case Attribute::BACKEND_TYPE_VARCHAR:
                default:
                    return $this->attribute->getAttributeCode() . '_t_mv';
            }
        }
    }

    /**
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
}