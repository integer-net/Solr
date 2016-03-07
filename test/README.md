# Unit Test Suite

The unit test suite does not need additional resources

## Run Tests:

In the root directory:

    phpunit --testsuite unit
    
# Integration Test Suite

The SolrSuggest integration test suite requires a Magento installation to test writing of the custom cache.
By default it looks in `../../htdocs`, but you can specify the Magento root dir with

    export MAGENTO_ROOT=/path/to/magento
    
Nothing will be written to filesystem and Magento database.

Fixtures have been automatically generated with Magento 1.9 sample data.

## Run Tests:

In the root directory:

    phpunit --testsuite integration
    
The EcomDev_PHPUnit Magento integration test suite can be found at
<a href="../src/app/code/community/IntegerNet/Solr/Test">src/app/code/community/IntegerNet/Solr/Test</a>