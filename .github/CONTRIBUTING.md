# Contribution Guide

The Laravel contributing guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Running Cashier Paddle's Tests

You will need to set some environment variables in a custom `phpunit.xml` file in order to run the Cashier Paddle tests.

Copy the default file using `cp phpunit.xml.dist phpunit.xml` and add the following lines below the `DB_CONNECTION` environment variable in your new `phpunit.xml` file:

    <env name="PADDLE_SANDBOX" value="true"/>
    <env name="PADDLE_VENDOR_ID" value="Your Paddle vendor ID"/>
    <env name="PADDLE_VENDOR_AUTH_CODE" value="Your Paddle auth code"/>
    <env name="PADDLE_TEST_PRODUCT" value="Identifier for a random one off product"/>

After setting these variables, you can run your tests by executing the `vendor/bin/phpunit` command.
