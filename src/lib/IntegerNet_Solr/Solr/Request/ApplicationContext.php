<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_Solr
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\Solr\Request;
use IntegerNet\Solr\Config\FuzzyConfig;
use IntegerNet\Solr\Config\ResultsConfig;
use IntegerNet\Solr\Implementor\AttributeRepository;
use IntegerNet\Solr\Implementor\EventDispatcher;
use IntegerNet\Solr\Implementor\Pagination;
use IntegerNet\Solr\Implementor\HasUserQuery;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Holds application context (bridge objects) for RequestFactory
 *
 * @package IntegerNet\Solr\Factory
 */
final class ApplicationContext
{
    /**
     * @var $attributeRepository AttributeRepository
     */
    private $attributeRepository;
    /**
     * @var $resultsConfig ResultsConfig
     */
    private $resultsConfig;
    /**
     * @var $fuzzyConfig FuzzyConfig
     */
    private $fuzzyConfig;
    /**
     * @var $pagination Pagination
     */
    private $pagination;
    /**
     * @var $query HasUserQuery
     */
    private $query;
    /**
     * @var $eventDispatcher EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var $logger LoggerInterface
     */
    private $logger;

    /**
     * @param AttributeRepository $attributeRepository
     * @param ResultsConfig $resultsConfig
     * @param Pagination $pagination
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(AttributeRepository $attributeRepository, ResultsConfig $resultsConfig,
                                Pagination $pagination, EventDispatcher $eventDispatcher, LoggerInterface $logger)
    {
        $this->attributeRepository = $attributeRepository;
        $this->resultsConfig = $resultsConfig;
        $this->pagination = $pagination;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @param FuzzyConfig $fuzzyConfig
     * @return ApplicationContext
     */
    public function setFuzzyConfig($fuzzyConfig)
    {
        $this->fuzzyConfig = $fuzzyConfig;
        return $this;
    }

    /**
     * @param HasUserQuery $query
     * @return ApplicationContext
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return AttributeRepository
     */
    public function getAttributeRepository()
    {
        return $this->attributeRepository;
    }

    /**
     * @return ResultsConfig
     */
    public function getResultsConfig()
    {
        return $this->resultsConfig;
    }

    /**
     * @return FuzzyConfig
     */
    public function getFuzzyConfig()
    {
        if ($this->fuzzyConfig === null) {
            throw new UnexpectedValueException('ApplicationContext::$fuzzyConfig is not initialized.');
        }
        return $this->fuzzyConfig;
    }

    /**
     * @return Pagination
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * @return HasUserQuery
     */
    public function getQuery()
    {
        if ($this->query === null) {
            throw new UnexpectedValueException('ApplicationContext::$query is not initialized.');
        }
        return $this->query;
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

}