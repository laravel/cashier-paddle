<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;

class ChargesTest extends FeatureTestCase
{
    public function test_customers_can_retrieve_a_single_charge_link()
    {
        $customer = $this->createCustomer();

        $url = $customer->charge(0, 'Test Product');

        $this->assertStringContainsString(Cashier::BASE_URL.'/checkout/custom/', $url);
    }

    public function test_customers_can_retrieve_a_product_charge_link()
    {
        $customer = $this->createCustomer();

        $url = $customer->chargeProduct($_ENV['PADDLE_TEST_PRODUCT']);

        $this->assertStringContainsString(Cashier::BASE_URL.'/checkout/custom/', $url);
    }

    // This doesn't works atm because we don't have a order.
    // public function test_customers_can_refund_a_product_charge_link()
    // {
    //     $customer = $this->createCustomer();
    //
    //     $result = $customer->refund($_ENV['PADDLE_TEST_ORDER']);
    //
    //     $this->assertTrue($result);
    // }
}
