<?php
/**
 * integer_net Magento Module
 *
 * @category   IntegerNet
 * @package
 * @copyright  Copyright (c) 2016 integer_net GmbH (http://www.integer-net.de/)
 * @author     Fabian Schmengler <fs@integer-net.de>
 */

namespace IntegerNet\SolrSuggest\Plain\Block;


use IntegerNet\Solr\Exception;
use IntegerNet\SolrSuggest\Block\AbstractCustomHelper;
use IntegerNet\SolrSuggest\Block\DefaultCustomHelper;
use IntegerNet\SolrSuggest\Implementor\AutosuggestBlock;
use IntegerNet\SolrSuggest\Plain\Cache\CustomCache;

/**
 * Factory for custom helper, configured with include path and class name. Can be serialized in custom cache
 *
 * @see CustomCache
 * @package IntegerNet\SolrSuggest\Plain\Block
 */
class CustomHelperFactory
{
    /**
     * @var string Absolute path to class file
     */
    private $pathToClassFile;
    /**
     * @var string Fully qualified class name
     */
    private $className;

    /**
     * CustomHelperFactory constructor.
     * @param string $pathToClassFile
     * @param string $className
     */
    public function __construct($pathToClassFile, $className)
    {
        $this->pathToClassFile = $pathToClassFile;
        $this->className = $className;
    }

    /**
     * Instantiate custom helper with given parameters
     *
     * @param AutosuggestBlock $block
     * @param CustomCache $customCache
     * @return AbstractCustomHelper
     * @throws Exception
     */
    public function getCustomHelper(AutosuggestBlock $block, CustomCache $customCache)
    {
        if ($this->className == '') {
            return new DefaultCustomHelper($block, $customCache);
        }
        $fileIncluded = false;
        if (file_exists($this->pathToClassFile) || stream_resolve_include_path($this->pathToClassFile)) {
            require_once $this->pathToClassFile;
            $fileIncluded = true;
        }
        if (class_exists($this->className)) {
            $class = $this->className;
            return new $class($block, $customCache);
        } else {
            $message = "Custom helper {$this->className} not found.";
            if ($fileIncluded) {
                $message .= " Included file {$this->pathToClassFile}";
            } else {
                $message .= " Could not find file to include: {$this->pathToClassFile}";
            }
            throw new Exception($message);
        }
    }
}