# Contribution Guide

The Laravel contributing guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Environment Setup

In order to run the test suite locally you'll have to set up a Paddle account and define a product and a subscription. By default if you don't set them up, the tests for these are skipped.

- `PADDLE_VENDOR_ID`: Your Paddle vendor ID
- `PADDLE_VENDOR_AUTH_CODE`: Your Paddle auth code
- `PADDLE_TEST_PRODUCT`: Identifier for a random one off product
- `PADDLE_TEST_SUBSCRIPTION`: Identifier for a random customer's subscription. You'll need to set up this manually through an app.

Create a `phpunit.xml` file with the following content and these keys:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit backupGlobals="false"
         backupStaticAttributes="false"
         bootstrap="vendor/autoload.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <php>
        <server name="DB_CONNECTION" value="testing"/>
        <server name="PADDLE_VENDOR_ID" value=""/>
        <server name="PADDLE_VENDOR_AUTH_CODE" value=""/>
        <server name="PADDLE_TEST_PRODUCT" value=""/>
        <server name="PADDLE_TEST_SUBSCRIPTION" value=""/>
    </php>
</phpunit>
```

Now you can run your tests by running `vendor/bin/phpunit`.
