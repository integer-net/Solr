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
            $storeIds = $this->_getStoreIds($storeIdentifiers);

            $entityTypes = $this->getArg('type');
            if (!$entityTypes || $entityTypes == 'all') {
                $entityTypes = $this->_getDefaultEntityTypes();
            } else {
                $entityTypes = explode(',', $entityTypes);
            }

            $emptyIndex = true;
            if ($this->getArg('emptyindex')) {
                $emptyIndex = 'force';
            } else if ($this->getArg('noemptyindex')) {
                $emptyIndex = false;
            }

            $autoloader = new IntegerNet_Solr_Helper_Autoloader();
            $autoloader->createAndRegister();

            try {
                if (in_array('product', $entityTypes)) {
                    $indexer = Mage::helper('integernet_solr/factory')->getProductIndexer();
                    $indexer->reindex(null, $emptyIndex, $storeIds);
                    $storeIdsString = implode(', ', $storeIds);
                    echo "Solr product index rebuilt for Stores {$storeIdsString}.\n";
                }

                if (in_array('page', $entityTypes)) {
                    $indexer = Mage::helper('integernet_solr/factory')->getPageIndexer();
                    $indexer->reindex(null, $emptyIndex, $storeIds);
                    $storeIdsString = implode(', ', $storeIds);
                    echo "Solr page index rebuilt for Stores {$storeIdsString}.\n";
                }
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
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
  --types <types>    Restrict indexing to certain entity types, i.e. "product" or "page" (comma separated). Or "all". If not set, reindex products.
  help              This help

USAGE;
    }

    /**
     * @param mixed[] $storeIdentifiers
     * @return int[]
     */
    protected function _getStoreIds($storeIdentifiers)
    {
        $storeIds = array();
        foreach (explode(',', $storeIdentifiers) as $storeIdentifier) {
            $storeIdentifier = trim($storeIdentifier);
            if ($storeIdentifier == 'all') {
                $storeIds = array();
                foreach (Mage::app()->getStores(false) as $store) {
                    if ($store->getIsActive() && Mage::getStoreConfigFlag('integernet_solr/general/is_active', $store->getId())) {
                        $storeIds[] = $store->getId();
                    }
                }
                return $storeIds;
            }
            $store = Mage::app()->getStore($storeIdentifier);
            if ($store->getIsActive() && Mage::getStoreConfigFlag('integernet_solr/general/is_active', $store->getId())) {
                $storeIds[] = $store->getId();
            }
        }
        return $storeIds;
    }

    /**
     * @return array
     */
    protected function _getDefaultEntityTypes()
    {
        return array('product', 'page');
    }
}

$shell = new IntegerNet_Solr_Shell();
$shell->run();
