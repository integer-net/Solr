<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2015 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Request;

use IntegerNet\Solr\Config\Stub\AutosuggestConfigBuilder;
use IntegerNet\Solr\Config\Stub\ResultConfigBuilder;
use IntegerNet\Solr\Implementor\Stub\AttributeRepositoryStub;
use IntegerNet\Solr\Request\ApplicationContext;
use IntegerNet\Solr\Resource\ResourceFacade;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use IntegerNet\Solr\Implementor\EventDispatcher;
use Psr\Log\LoggerInterface;
use IntegerNet\Solr\Implementor\HasUserQuery;
use IntegerNet\SolrSuggest\Request\SearchTermSuggestRequest;

class SearchTermSuggestRequestFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateRequest()
    {
        $storeId = 0;
        $queryText = 'peace';

        $queryStub = $this->getMockForAbstractClass(HasUserQuery::class);
        $queryStub->expects($this->any())->method('getUserQueryText')->willReturn($queryText);
        $applicationContext = new ApplicationContext(
            new AttributeRepositoryStub(),
            ResultConfigBuilder::defaultConfig()->build(),
            AutosuggestConfigBuilder::defaultConfig()->build(),
            $this->getMockForAbstractClass(EventDispatcher::class),
            $this->getMockForAbstractClass(LoggerInterface::class)
        );
        $applicationContext->setQuery($queryStub);
        $resource = new ResourceFacade();

        $factoryUnderTest = new SearchTermSuggestRequestFactory($applicationContext, $resource, $storeId);
        $actualRequest = $factoryUnderTest->createRequest();
        $this->assertInstanceOf(SearchTermSuggestRequest::class, $actualRequest);
    }
}