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
     * @param Attribute $attribute
     * @@param boolean $forSorting
     */
    public function __construct(Attribute $attribute, $forSorting = false)
    {
        $this->attribute = $attribute;
        $this->forSorting = $forSorting;
    }

    public function forSorting($forSorting = true)
    {
        return new self($this->attribute, $forSorting);
    }

    public function getFieldName()
    {
        if ($this->attribute->getUsedForSortBy()) {
            switch ($this->attribute->getBackendType()) {
                case 'decimal':
                    return $this->attribute->getAttributeCode() . '_f';

                case 'text':
                    return $this->attribute->getAttributeCode() . '_t';

                default:
                    return ($this->forSorting) ? $this->attribute->getAttributeCode() . '_s' : $this->attribute->getAttributeCode() . '_t';
            }
        } else {
            switch ($this->attribute->getBackendType()) {
                case 'decimal':
                    return $this->attribute->getAttributeCode() . '_f_mv';

                case 'text':
                    return $this->attribute->getAttributeCode() . '_t_mv';

                default:
                    return $this->attribute->getAttributeCode() . '_t_mv';
            }
        }
    }

}