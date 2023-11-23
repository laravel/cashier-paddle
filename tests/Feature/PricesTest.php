<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\PricePreview;

class PricesTest extends FeatureTestCase
{
    public function test_it_can_fetch_the_prices_of_products()
    {
        if (! getenv('PADDLE_TEST_PRICE')) {
            $this->markTestSkipped('Test price not configured.');
        }

        $prices = Cashier::previewPrices([getenv('PADDLE_TEST_PRICE')]);

        $this->assertNotEmpty($prices);
        $this->assertContainsOnlyInstancesOf(PricePreview::class, $prices->all());
    }
}
