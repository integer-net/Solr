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

    /** @var null|IntegerNet_Solr_Model_Resource_Solr_Service */
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

                $this->_logResult();
            }
        }

        return $this->_solrResult;
    }

    /**
     * @return int
     */
    protected function _getCurrentPage()
    {
        if (!$this->_getToolbarBlock()) {
            return 0;
        }
        return $this->_getToolbarBlock()->getCurrentPage() - 1;
    }

    /**
     * @return int
     */
    protected function _getCurrentSort()
    {
        if (!$this->_getToolbarBlock()) {
            return 'position';
        }
        return $this->_getToolbarBlock()->getCurrentOrder();
    }

    /**
     * @return int
     */
    protected function _getCurrentSortDirection()
    {
        if (!$this->_getToolbarBlock()) {
            return 'desc';
        }
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
        if (!$this->_getToolbarBlock()) {
            return intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_product_suggestions'));
        }
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
            'fl' => 'result_html_list_nonindex,result_html_grid_nonindex,result_html_autosuggest_nonindex,score,sku_s,name_s',
            'qf' => $this->_getSearchFieldCodes(),
            'sort' => $this->_getSortParam(),
            'facet' => 'true',
            'facet.sort' => 'true',
            'facet.mincount' => '1',
            'facet.field' => $this->_getFacetFieldCodes(),
        );

        if (!$this->_getToolbarBlock()) {
            $params['rows'] = intval(Mage::getStoreConfig('integernet_solr/autosuggest/max_number_product_suggestions'));
        }


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
     * @return array
     */
    protected function _getSearchFieldCodes()
    {
        $codes = array('category');
        foreach(Mage::helper('integernet_solr')->getSearchableAttributes() as $attribute) {
            $codes[] = Mage::helper('integernet_solr')->getFieldName($attribute);
        }
        return $codes;
    }

    /**
     * @return string
     */
    protected function _getQueryText()
    {
        $query = Mage::helper('catalogsearch')->getQuery();
        $queryText = $query->getQueryText();
        if ($query->getSynonymFor()) {
            $queryText = $query->getSynonymFor();
        }
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
     * @param int $storeId
     * @return string
     */
    protected function _getFilterQuery($storeId)
    {
        $filterQuery = 'store_id:' . $storeId;
        
        foreach($this->getFilters() as $attributeCode => $value) { 
            $filterQuery .= ' AND ' . $attributeCode . ':' . $value;
        }
        
        return $filterQuery;
    }

    /**
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     * @param int $value
     */
    public function addAttributeFilter($attribute, $value) 
    {
        $this->_filters[$attribute->getAttributeCode() . '_facet'] = $value;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     */
    public function addCategoryFilter($category) 
    {
        $this->_filters['category'] = $category->getId();
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    protected function _logResult()
    {
        $resultClone = unserialize(serialize($this->_solrResult));
        if (isset($resultClone->response->docs)) {
            foreach ($resultClone->response->docs as $key => $doc) {
                /** @var Apache_Solr_Document $doc */
                foreach ($doc->getFieldNames() as $fieldName) {
                    $field = $doc->getField($fieldName);
                    $value = $field['value'];
                    if (is_array($value)) {
                        foreach($value as $subKey => $subValue) {
                            $subValue = str_replace(array("\n", "\r"), '', $subValue);
                            if (strlen($subValue) > 50) {
                                $subValue = substr($subValue, 0, 50) . '...';
                                $value[$subKey] = $subValue;
                                $doc->setField($fieldName, $value);
                                $resultClone->response->docs[$key] = $doc;
                            }
                        }
                    } else {
                        $value = str_replace(array("\n", "\r"), '', $value);
                        if (strlen($value) > 50) {
                            $value = substr($value, 0, 50) . '...';
                            $doc->setField($fieldName, $value);
                            $resultClone->response->docs[$key] = $doc;
                        }
                    }
                }
            }
        }
        Mage::log($resultClone, null, 'solr.log');
    }
}