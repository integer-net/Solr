<?php
/**
 * Use this PHPUnit bootstrap file for EcomDev_PHPUnit integration tests if you installed the library with composer
 * and use the Hackathon PSR0 Autoloader. Example:
 *
 *     phpunit --group IntegerNet_Solr --bootstrap vendor/integer_net/solr/test/bootstrap-hackathon-autoloader.php
 */
require_once 'app/Mage.php';
Mage::app();
Mage::getConfig()->init()->loadEventObservers('global');
Mage::app()->addEventArea('global');
Mage::dispatchEvent('add_spl_autoloader');