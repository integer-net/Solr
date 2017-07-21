<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Block;


final class AttributeSuggestion
{
    /**
     * @var string
     */
    private $attributeCode;
    /**
     * @var string
     */
    private $label;
    /**
     * @var AttributeOptionSuggestion[]
     */
    private $options;

    /**
     * @param string $attributeCode
     * @param string $label
     * @param AttributeOptionSuggestion[] $options
     */
    public function __construct($attributeCode, $label, array $options)
    {
        $this->attributeCode = $attributeCode;
        $this->label = $label;
        $this->options = $options;
    }


    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return AttributeOptionSuggestion[]
     */
    public function getOptions()
    {
        return $this->options;
    }


}