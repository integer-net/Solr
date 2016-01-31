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

use Psr\Log\AbstractLogger;
use Zend_Log;
use Psr\Log\LogLevel;
use Zend_Log_Formatter_Simple;
use Zend_Log_Writer_Stream;

//TODO remove Zend_Log dependency or make logger exchangable
final class Logger extends AbstractLogger
{
    /**
     * @var array
     */
    protected static $_levelMapping = array(
        LogLevel::ALERT     => Zend_Log::ALERT,
        LogLevel::CRITICAL  => Zend_Log::CRIT,
        LogLevel::DEBUG     => Zend_Log::DEBUG,
        LogLevel::EMERGENCY => Zend_Log::EMERG,
        LogLevel::ERROR     => Zend_Log::ERR,
        LogLevel::INFO      => Zend_Log::INFO,
        LogLevel::NOTICE    => Zend_Log::NOTICE,
        LogLevel::WARNING   => Zend_Log::WARN,
    );
    /**
     * @var string
     */
    private $file = 'solr.log';

    /**
     * @param string $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $level = self::$_levelMapping[$level];
        $level  = is_null($level) ? Zend_Log::DEBUG : $level;
        $file = $this->file;

        static $loggers = array();
        try {
            if (!isset($loggers[$file])) {
                $logDir  = 'var' . DS . 'log';
                $logFile = $logDir . DS . $file;

                if (!is_dir($logDir)) {
                    mkdir($logDir);
                    chmod($logDir, 0777);
                }

                if (!file_exists($logFile)) {
                    file_put_contents($logFile, '');
                    chmod($logFile, 0777);
                }

                $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
                $formatter = new Zend_Log_Formatter_Simple($format);
                $writer = new Zend_Log_Writer_Stream($logFile);
                $writer->setFormatter($formatter);
                $loggers[$file] = new Zend_Log($writer);
            }

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            $loggers[$file]->log($message, $level);
        }
        catch (\Exception $e) {
        }
    }

}