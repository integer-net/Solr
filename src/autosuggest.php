<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Autosuggest
{
    public function getHtml()
    {
        if (!isset($_GET['q'])) {
            die('Query not given.');
        }

        $block = Mage::app()->getLayout()->createBlock('integernet_solr/autosuggest');

        return $block->toHtml();
    }
}

require_once 'app/Mage.php';
umask(0);

if (!isset($_GET['store_id'])) {
    die('Store ID not given.');
}

Mage::app()->setCurrentStore(intval($_GET['store_id']));

$autosuggest = new IntegerNet_Solr_Autosuggest();

echo $autosuggest->getHtml();