<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

/** @var Mage_Catalog_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$autoloader = new IntegerNet_Solr_Helper_Autoloader();
$autoloader->createAndRegister();

Mage::helper('integernet_solr/autosuggest')->storeSolrConfig();

$installer->endSetup();