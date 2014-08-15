<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2014 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_Solr_Model_Result
{
    /** @var null|IntegerNet_Solr_Model_Resource_Solr */
    protected $_resource = null;

    /** @var null|Apache_Solr_Response */
    protected $_solrResult = null;

    /** @var null|Mage_Catalog_Block_Product_List_Toolbar */
    protected $_toolbarBlock = null;
    
    protected $_filters = array();

    /**
     * @return IntegerNet_Solr_Model_Resource_Solr
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::getResourceModel('integernet_solr/solr');
        }

        return $this->_resource;
    }

    /**
     * @param $storeId
     * @return Apache_Solr_Response
     */
    public function getSolrResult($storeId = null)
    {
        if (is_null($this->_solrResult)) {
            if (is_null($storeId)) {
                $storeId = Mage::app()->getStore()->getId();
            }
            $this->_solrResult = $this->_getResource()->search(
                $storeId,
                $this->_getQueryText(),
                $this->_getCurrentPage() * $this->_getPageSize(), // Start item
                $this->_getPageSize(), // Items per page
                $this->_getParams($storeId)
            );

            if (Mage::getStoreConfigFlag('integernet_solr/general/log')) {

                $resultClone = unserialize(serialize($this->_solrResult));
                if (isset($resultClone->response->docs)) {
                    foreach($resultClone->response->docs as $key => $doc) {
                        /** @var Apache_Solr_Document $doc */
                        foreach($doc->getFieldNames() as $fieldName) {
                            $field = $doc->getField($fieldName);
                            $value = str_replace(array("\n", "\r"), '', $field['value']);
                            if (strlen($value) > 50) {
                                $value = substr($value, 0, 50) . '...';
                                $doc->setField($fieldName, $value);
                                $resultClone->response->docs[$key] = $doc;
                            }
                        }
                    }
                }
                Mage::log($resultClone, null, 'solr.log');
            }
        }

        return $this->_solrResult;
    }

    /**
     * @return int
     */
    protected function _getCurrentPage()
    {
        return $this->_getToolbarBlock()->getCurrentPage() - 1;
    }

    /**
     * @return int
     */
    protected function _getCurrentSort()
    {
        return $this->_getToolbarBlock()->getCurrentOrder();
    }

    /**
     * @return int
     */
    protected function _getCurrentSortDirection()
    {
        if ($this->_getCurrentSort() == 'position') {
            switch(strtolower($this->_getToolbarBlock()->getCurrentDirection())) {
                case 'desc':
                    return 'asc';
                default:
                    return 'desc';
            }
        }
        return $this->_getToolbarBlock()->getCurrentDirection();
    }

    /**
     * @return int
     */
    protected function _getPageSize()
    {
        return $this->_getToolbarBlock()->getLimit();
    }

    /**
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    protected function _getToolbarBlock()
    {
        if (is_null($this->_toolbarBlock)) {
            $this->_toolbarBlock = Mage::app()->getLayout()->getBlock('product_list_toolbar');
        }
        return $this->_toolbarBlock;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function _getParams($storeId)
    {
        $params = array(
            'fq' => $this->_getFilterQuery($storeId),
            'fl' => 'result_html_list_t,result_html_grid_t,score,sku_s,name_s',
            'sort' => $this->_getSortParam(),
            'facet' => 'true',
            'facet.sort' => 'true',
            'facet.mincount' => '1',
            'facet.field' => $this->_getFacetFieldCodes(),
        );

        return $params;
    }

    /**
     * @return array
     */
    protected function _getFacetFieldCodes()
    {
        $codes = array('category');
        foreach(Mage::helper('integernet_solr')->getFilterableInSearchAttributes() as $attribute) {
            $codes[] = $attribute->getAttributeCode() . '_facet';
        }
        return $codes;
    }

    /**
     * @return string
     */
    protected function _getQueryText()
    {
        $queryText = Mage::helper('catalogsearch')->getQuery()->getQueryText();
        if (Mage::getStoreConfigFlag('integernet_solr/fuzzy/is_active')) {
            $queryText .= '~' . Mage::getStoreConfig('integernet_solr/fuzzy/sensitivity');
        }
        return $queryText;
    }

    /**
     * @return string
     */
    protected function _getSortParam()
    {
        switch ($this->_getCurrentSort()) {
            case 'position':
                $param = 'score';
                break;
            case 'price':
                $param = 'price_f';
                break;
            default:
                $param = $this->_getCurrentSort() . '_s';
        }

        $param .= ' ' . $this->_getCurrentSortDirection();
        return $param;
    }

    /**
     * @param $storeId
     * @return string
     */
    protected function _getFilterQuery($storeId)
    {
        $filterQuery = 'store_id:' . $storeId;
        
        foreach($this->getFilters() as $attributeCode => $value) { 
            $filterQuery .= ' AND ' . $attributeCode . '_facet:' . $value;
        }
        
        return $filterQuery;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute$attribute
     * @param int $value
     */
    public function addFilter($attribute, $value) 
    {
        $this->_filters[$attribute->getAttributeCode()] = $value;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }
}