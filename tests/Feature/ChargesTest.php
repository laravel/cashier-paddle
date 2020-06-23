<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;

class ChargesTest extends FeatureTestCase
{
    public function test_customers_can_retrieve_a_single_charge_link()
    {
        $billable = $this->createBillable();

        $url = $billable->charge(0, 'Test Product');

        $this->assertStringContainsString(Cashier::checkoutUrl().'/checkout/custom/', $url);
    }

    public function test_customers_can_retrieve_a_product_charge_link()
    {
        $billable = $this->createBillable();

        $url = $billable->chargeProduct($_SERVER['PADDLE_TEST_PRODUCT']);

        $this->assertStringContainsString(Cashier::checkoutUrl().'/checkout/custom/', $url);
    }
}
