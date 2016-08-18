<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Andreas von Studnitz <avs@integer-net.de>
 */
class IntegerNet_SolrPro_Block_Result_Categories extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * @return IntegerNet_SolrPro_Model_Cms_Collection|Apache_Solr_Document[]
     */
    public function getResultsCollection()
    {
        return Mage::getSingleton('integernet_solrpro/categories_collection');
    }

    /**
     * @param Apache_Solr_Document $document
     * @return string
     */
    public function getCategoryPath($document)
    {
        return $document->path_s_nonindex;
    }

    /**
     * @param Apache_Solr_Document $document
     * @return string
     */
    public function getCategoryTitle($document)
    {
        return $document->name_t;
    }

    /**
     * @param Apache_Solr_Document $document
     * @return string
     */
    public function getCategoryAbstract($document)
    {
        if (isset($document->abstract_t_nonindex)) {
            return $document->abstract_t_nonindex;
        }
        return '';
    }

    /**
     * @param Apache_Solr_Document $document
     * @return string
     */
    public function getCategoryUrl($document)
    {
        return $document->url_s_nonindex;
    }

    /**
     * @param Apache_Solr_Document $document
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getCategoryImageUrl($document, $width, $height)
    {
        if (isset($document->image_url_s_nonindex) && ($imageUrl = $document->image_url_s_nonindex)) {
            return $this->helper('integernet_solrpro/timage')->init($imageUrl)->resize($width, $height);
        }
        return '';
    }
}