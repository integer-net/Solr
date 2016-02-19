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

use IntegerNet\Solr\Config\AutosuggestConfig;
use IntegerNet\Solr\Event\Transport;
use IntegerNet\Solr\Implementor\Attribute;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\SolrSuggest\Plain\Entity\SerializableAttribute;
use IntegerNet\SolrSuggest\Plain\Entity\SerializableAttributeRepository;

final class AttributesToSerializableAttributes implements SerializableAttributeRepository
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
     * @var AutosuggestConfig[]
     */
    private $autosuggestConfigByStore;

    /**
     * SerializableAttributeRepository constructor.
     * @param AttributeRepository $attributeRepository
     * @param EventDispatcher $eventDispatcher
     * @param AutosuggestConfig[] $autosuggestConfigByStore
     */
    public function __construct(AttributeRepository $attributeRepository, EventDispatcher $eventDispatcher,
                                array $autosuggestConfigByStore)
    {
        $this->attributeRepository = $attributeRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->autosuggestConfigByStore = $autosuggestConfigByStore;
    }

    /**
     * @internal
     * @param Attribute $attribute
     * @param int $storeId
     * @return \IntegerNet\SolrSuggest\Plain\Entity\Attribute
     */
    public function _convertAttribute(Attribute $attribute, $storeId)
    {
        $transport = new Transport();
        $this->eventDispatcher->dispatch(self::EVENT_ATTRIBUTE_CUSTOM_DATA, [
            'transport' => $transport,
            'attribute' => $attribute,
            'store_id' => $storeId
        ]);
        return new \IntegerNet\SolrSuggest\Plain\Entity\Attribute(
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
        $allowedAttributeCodes = array_map(
            function($row) { return $row['attribute_code']; },
            $this->autosuggestConfigByStore[$storeId]->getAttributeFilterSuggestions());
        return array_map(
            function (Attribute $attribute) use ($self, $storeId) {
                return $self->_convertAttribute($attribute, $storeId);
            },
            array_filter(
                $this->attributeRepository->getFilterableInSearchAttributes($storeId),
                function (Attribute $attribute) use ($allowedAttributeCodes) {
                    return in_array($attribute->getAttributeCode(), $allowedAttributeCodes);
                }
            )
        );
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