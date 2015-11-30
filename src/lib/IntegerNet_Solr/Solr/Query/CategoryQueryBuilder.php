<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Query;

final class CategoryQueryBuilder extends AbstractQueryBuilder
{
    /**
     * @var $categoryId int
     */
    private $categoryId;

    protected function getQueryText()
    {
        return 'category_' . $this->categoryId . '_position_i:*';
    }

}