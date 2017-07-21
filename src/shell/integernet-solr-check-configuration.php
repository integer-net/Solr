<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */

require_once 'abstract.php';

class IntegerNet_Solr_Shell extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        $autoloader = new IntegerNet_Solr_Helper_Autoloader();
        $autoloader->createAndRegister();

        Mage::getModel('integernet_solr/observer')->checkSolrServerConnection();
    }
}

$shell = new IntegerNet_Solr_Shell();
$shell->run();
