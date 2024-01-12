# Contribution Guide

The Laravel contributing guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Running Cashier Paddle's Tests

You will need to set some environment variables in a custom `phpunit.xml` file in order to run the Cashier Paddle tests.

Copy the default file using `cp phpunit.xml.dist phpunit.xml` and add the following lines below the `DB_CONNECTION` environment variable in your new `phpunit.xml` file:

    <env name="PADDLE_SANDBOX" value="true"/>
    <env name="PADDLE_SELLER_ID" value="Your Paddle seller ID"/>
    <env name="PADDLE_API_KEY" value="Your Paddle api key"/>
    <env name="PADDLE_TEST_PRICE" value="Identifier for a random one off price"/>

After setting these variables, you can run your tests by executing the `vendor/bin/phpunit` command.
