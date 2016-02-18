<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Cache\Convert;

use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrSuggest\Implementor\SerializableAttribute;

final class SerializableAttributeRepository implements \IntegerNet\SolrSuggest\Implementor\SerializableAttributeRepository
{
    const EVENT_ATTRIBUTE_CUSTOM_DATA = 'integernet_solr_autosuggest_config_attribute';
    /**
     * @var AttributeRepository
     */
    private $attributeRepository;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * SerializableAttributeRepository constructor.
     * @param AttributeRepository $attributeRepository
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(AttributeRepository $attributeRepository, EventDispatcher $eventDispatcher)
    {
        $this->attributeRepository = $attributeRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @internal
     * @param Attribute $attribute
     * @param int $storeId
     * @return \IntegerNet\SolrSuggest\Plain\Bridge\Attribute
     */
    public function _convertAttribute(Attribute $attribute, $storeId)
    {
        $transport = new Transport();
        $this->eventDispatcher->dispatch(self::EVENT_ATTRIBUTE_CUSTOM_DATA, [
            'transport' => $transport,
            'attribute' => $attribute,
            'store_id' => $storeId
        ]);
        return new \IntegerNet\SolrSuggest\Plain\Bridge\Attribute(
            $attribute->getAttributeCode(), $attribute->getStoreLabel(),
            $attribute->getSolrBoost(), $attribute->getSource(), $attribute->getUsedForSortBy(), $transport->getArrayCopy()
        );
    }

    /**
     * @param int $storeId
     * @return SerializableAttribute[]
     */
    public function findFilterableInSearchAttributes($storeId)
    {
        $self = $this;
        return array_map(function(Attribute $attribute) use ($self, $storeId) {
            return $self->_convertAttribute($attribute, $storeId);
        }, $this->attributeRepository->getFilterableInSearchAttributes($storeId));
    }

    /**
     * @param $storeId
     * @return SerializableAttribute[]
     */
    public function findSearchableAttributes($storeId)
    {
        $self = $this;
        return array_map(function(Attribute $attribute) use ($self, $storeId) {
            return $self->_convertAttribute($attribute, $storeId);
        }, $this->attributeRepository->getSearchableAttributes($storeId));
    }
}