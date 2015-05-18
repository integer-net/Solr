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
        if ($this->getArg('reindex')) {
            $storeIdentifiers = $this->getArg('stores');
            if (!$storeIdentifiers) {
                $storeIdentifiers = 'all';
            }
            $stores = $this->_getStores($storeIdentifiers);

            $emptyIndex = true;
            if ($this->getArg('emptyindex')) {
                $emptyIndex = 'force';
            } else if ($this->getArg('noemptyindex')) {
                $emptyIndex = false;
            }

            foreach($stores as $store) {
                Mage::getSingleton('integernet_solr/indexer_product')->reindex(null, $emptyIndex, $store);
                echo "Solr index rebuilt for Store '{$store->getCode()}'.\n";
            }

        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f integernet-solr.php -- [options]
        php -f integernet-solr.php -- reindex --stores de
        php -f integernet-solr.php -- reindex --stores all --emptyindex

  reindex           reindex solr for given stores (see "stores" param)
  --stores <stores> reindex given stores (can be store id, store code, comma seperated. Or "all".) If not set, reindex all stores.
  --emptyindex      Force emptying the solr index for the given store(s). If not set, configured value is used.
  --noemptyindex    Force not emptying the solr index for the given store(s). If not set, configured value is used.
  help              This help

USAGE;
    }

    /**
     * @param mixed[] $storeIdentifiers
     * @return Mage_Core_Model_Store[]
     */
    protected function _getStores($storeIdentifiers)
    {
        $stores = array();
        foreach (explode(',', $storeIdentifiers) as $storeIdentifier) {
            $storeIdentifier = trim($storeIdentifier);
            if ($storeIdentifier == 'all') {
                return Mage::app()->getStores();
            }
            $stores[] = Mage::app()->getStore($storeIdentifier);
        }
        return $stores;
    }
}

$shell = new IntegerNet_Solr_Shell();
$shell->run();
