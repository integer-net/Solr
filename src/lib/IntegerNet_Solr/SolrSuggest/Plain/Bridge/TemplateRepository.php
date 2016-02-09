<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Bridge;

use IntegerNet\Solr\Exception;
use IntegerNet\SolrSuggest\Implementor\Template;

class TemplateRepository implements \IntegerNet\SolrSuggest\Implementor\TemplateRepository
{
    /**
     * @var Template[]
     */
    private $templates;

    /**
     * @param int $storeId
     * @return Template
     * @throws Exception
     */
    public function getTemplateByStoreId($storeId)
    {
        if (! array_key_exists($storeId, $this->templates)) {
            throw new Exception('No template registered for store id ' . $storeId);
        }
        return $this->templates[$storeId];
    }


}