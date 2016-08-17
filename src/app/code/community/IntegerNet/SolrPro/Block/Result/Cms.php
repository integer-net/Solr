<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Block_Result_Cms extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * @return IntegerNet_SolrPro_Model_Cms_Collection|Apache_Solr_Document[]
     */
    public function getResultsCollection()
    {
        return Mage::getSingleton('integernet_solrpro/cms_collection');
    }

    /**
     * Retrieve product amount per row
     *
     * @return int
     */
    public function getColumnCount()
    {
        if (!$this->_getData('column_count')) {
            $pageLayout = $this->getPageLayout();
            if ($pageLayout && $this->getColumnCountLayoutDepend($pageLayout->getCode())) {
                $this->setData(
                    'column_count',
                    $this->getColumnCountLayoutDepend($pageLayout->getCode())
                );
            } else {
                $this->setData('column_count', $this->_defaultColumnCount);
            }
        }

        return (int) $this->_getData('column_count');
    }

    /**
     * @param Apache_Solr_Document $document
     * @return string
     */
    public function getCmsPageTitle($document)
    {
        return $document->title_t;
    }

    /**
     * @param Apache_Solr_Document $document
     * @return string
     */
    public function getCmsPageUrl($document)
    {
        return $document->url_s_nonindex;
    }

    /**
     * @param Apache_Solr_Document $document
     * @return string
     */
    public function getCmsPageImageUrl($document)
    {
        if (isset($document->image_url_s_nonindex)) {
            return $document->image_url_s_nonindex;
        }
        return '';
    }
}