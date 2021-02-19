<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\ProductPrice;

class PricesTest extends FeatureTestCase
{
    public function test_it_can_fetch_the_prices_of_products()
    {
        if (! isset($_SERVER['PADDLE_TEST_PRODUCT'])) {
            $this->markTestSkipped('Test product not configured.');
        }

        $prices = Cashier::productPrices([$_SERVER['PADDLE_TEST_PRODUCT']]);

        $this->assertNotEmpty($prices);
        $this->assertContainsOnlyInstancesOf(ProductPrice::class, $prices->all());
    }
}
