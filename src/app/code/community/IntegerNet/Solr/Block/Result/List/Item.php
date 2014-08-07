<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Block_Result_List_Item extends Mage_Core_Block_Abstract
{
    /**
     * Override this method in descendants to produce html
     *
     * @return string
     */
    protected function _toHtml()
    {
        /** @var Apache_Solr_Document $product */
        $product = $this->getProduct();

        $field = $product->getField('result_html_t');
        return $field['value'];
    }
}