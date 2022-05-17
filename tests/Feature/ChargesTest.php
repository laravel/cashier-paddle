<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;

class ChargesTest extends FeatureTestCase
{
    public function test_customers_can_retrieve_a_single_charge_link()
    {
        if (! getenv('PADDLE_VENDOR_ID') || ! getenv('PADDLE_VENDOR_AUTH_CODE')) {
            $this->markTestSkipped('Paddle vendor ID and auth code not configured.');
        }

        $billable = $this->createBillable();

        $url = $billable->charge(0, 'Test Product');

        $this->assertStringContainsString('/checkout/custom/', $url);
    }

    public function test_customers_can_retrieve_a_product_charge_link()
    {
        if (! getenv('PADDLE_TEST_PRODUCT')) {
            $this->markTestSkipped('Test product not configured.');
        }

        $billable = $this->createBillable();

        $url = $billable->chargeProduct(getenv('PADDLE_TEST_PRODUCT'));

        $this->assertStringContainsString('/checkout/custom/', $url);
    }

    public function test_payments_can_be_refunded()
    {
        Cashier::fake([
            'payment/refund' => [
                'success' => true,
                'response' => [
                    'refund_request_id' => 12345,
                ],
            ],
        ]);

        $billable = $this->createBillable();


        $response = $billable->refund(4321, 12.50, 'Incorrect order');

        $this->assertSame(12345, $response);
    }
}
