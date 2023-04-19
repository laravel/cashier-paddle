<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\ProductPrice;

class PricesTest extends FeatureTestCase
{
    public function test_it_can_fetch_the_prices_of_products()
    {
        if (! getenv('PADDLE_TEST_PRODUCT')) {
            $this->markTestSkipped('Test product not configured.');
        }

        $prices = Cashier::productPrices([getenv('PADDLE_TEST_PRODUCT')]);

        $this->assertNotEmpty($prices);
        $this->assertContainsOnlyInstancesOf(ProductPrice::class, $prices->all());
    }

    public function test_it_can_fetch_the_prices_of_multiple_products_and_sorts_them_based_on_the_input()
    {
        if (! getenv('PADDLE_TEST_PRODUCT') || ! getenv('PADDLE_TEST_PRODUCT_LOWER_ID')) {
            $this->markTestSkipped('Test products not configured.');
        }

        $prices = Cashier::productPrices([getenv('PADDLE_TEST_PRODUCT'), getenv('PADDLE_TEST_PRODUCT_LOWER_ID')]);

        $this->assertNotEmpty($prices);
        $this->assertCount(2, $prices);
        $this->assertContainsOnlyInstancesOf(ProductPrice::class, $prices->all());
        $this->assertEquals($prices[0]->product_id, getenv('PADDLE_TEST_PRODUCT'));
        $this->assertEquals($prices[1]->product_id, getenv('PADDLE_TEST_PRODUCT_LOWER_ID'));
    }

    public function test_it_can_fetch_the_prices_of_products_when_the_input_is_a_string()
    {
        if (! getenv('PADDLE_TEST_PRODUCT') || ! getenv('PADDLE_TEST_PRODUCT_LOWER_ID')) {
            $this->markTestSkipped('Test products not configured.');
        }

        $prices = Cashier::productPrices(getenv('PADDLE_TEST_PRODUCT'));

        $this->assertNotEmpty($prices);
        $this->assertContainsOnlyInstancesOf(ProductPrice::class, $prices->all());
        $this->assertEquals($prices[0]->product_id, getenv('PADDLE_TEST_PRODUCT'));
    }
}
