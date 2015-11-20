<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
interface IntegerNet_Solr_Implementor_AttributeRepository
{
    /**
     * @todo convert to IntegerNet_Solr_Implementor_Attribute array, maybe add getSearchableAttributeCodes()
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getSearchableAttributes();

    /**
     * @param bool $useAlphabeticalSearch
     * @return IntegerNet_Solr_Implementor_Attribute[]
     */
    public function getFilterableAttributes($useAlphabeticalSearch = true);

    /**
     * @param bool $useAlphabeticalSearch
     * @return IntegerNet_Solr_Implementor_Attribute[]
     */
    public function getFilterableInSearchAttributes($useAlphabeticalSearch = true);

    /**
     * @param bool $useAlphabeticalSearch
     * @return IntegerNet_Solr_Implementor_Attribute[]
     */
    public function getFilterableInCatalogAttributes($useAlphabeticalSearch = true);

    /**
     * @param bool $useAlphabeticalSearch
     * @return Mage_Catalog_Model_Entity_Attribute[]
     */
    public function getFilterableInCatalogOrSearchAttributes($useAlphabeticalSearch = true);
}