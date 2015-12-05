<?php
define('CLASS_DIR', realpath(__DIR__ . '/../src/lib'));
set_include_path(get_include_path().PATH_SEPARATOR.CLASS_DIR);
spl_autoload_register(function($className)
{
    if (strpos($className, 'IntegerNet\Solr') === 0) {
        $className = str_replace('IntegerNet\Solr', 'IntegerNet_Solr\Solr', $className);
    } elseif (strpos($className, 'Psr\Log') === 0) {
        $className = str_replace('Psr\Log', 'IntegerNet_Solr\Psr_Log', $className);
    }

    $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (stream_resolve_include_path($fileName)) {
        include $fileName;
        return true;
    }

    $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    if (stream_resolve_include_path($fileName)) {
        include $fileName;
        return true;
    }
    return false;
}
);