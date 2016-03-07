<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package    IntegerNet_SolrSuggest
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */
namespace IntegerNet\SolrSuggest\Plain\Factory;

use IntegerNet\Solr\Config\Stub\GeneralConfigBuilder;
use IntegerNet\Solr\Config\Stub\StoreConfigBuilder;
use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;

class LoggerFactoryTest extends \PHPUnit_Framework_TestCase
{
    const LOG_FILENAME = 'filename.log';
    /**
     * @var GeneralConfigBuilder
     */
    private $generalConfigBuilder;
    /**
     * @var StoreConfigBuilder
     */
    private $storeConfigBuilder;
    /**
     * @var string
     */
    private $virtualLogDir;

    protected function setUp()
    {
        vfsStream::setup('log');
        $this->virtualLogDir = vfsStream::url('log');
        $this->generalConfigBuilder = GeneralConfigBuilder::defaultConfig();
        $this->storeConfigBuilder = StoreConfigBuilder::defaultConfig()->withLogDir($this->virtualLogDir);

    }
    /**
     * @test
     */
    public function shouldWriteToConfiguredLogDir()
    {
        $existingLogContent = "existing log content\n";
        file_put_contents($this->virtualLogDir . '/' . self::LOG_FILENAME, $existingLogContent);
        $loggerFactory = new LoggerFactory($this->generalConfigBuilder->build(), $this->storeConfigBuilder->build());
        $logger = $loggerFactory->getLogger(self::LOG_FILENAME);
        $logger->debug('this is a debug log');
        $logger->error('this is an error log');

        $logFileContent = file_get_contents($this->virtualLogDir . '/' . self::LOG_FILENAME);
        $this->assertContains($existingLogContent, $logFileContent);
        $this->assertRegExp('{\[[\d.: -]+\] \[debug\] this is a debug log}', $logFileContent);
        $this->assertRegExp('{\[[\d.: -]+\] \[error\] this is an error log}', $logFileContent);
    }

    /**
     * @test
     */
    public function shouldUseNullLoggerIfLoggingNotActive()
    {
        $loggerFactory = new LoggerFactory($this->generalConfigBuilder->withLog(false)->build(), $this->storeConfigBuilder->build());
        $logger = $loggerFactory->getLogger(self::LOG_FILENAME);
        $this->assertInstanceOf(NullLogger::class, $logger);
    }
}