<?php

namespace Tests\Unit;

use Laravel\Paddle\Price;
use Laravel\Paddle\ProductPrice;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    const DEFAULTS = [
        'product_id' => 232,
        'product_title' => 232,
        'currency' => 'EUR',
        'price' => [
            'gross' => 12.45,
            'net' => '6.25',
            'tax' => 3.24,
        ],
        'list_price' => [
            'gross' => 12.45,
            'net' => '6.25',
            'tax' => 3.24,
        ],
        'subscription' => [
            'trial_days' => 5,
            'interval' => 'monthly',
            'frequency' => 1,
            'price' => [
                'gross' => 12.45,
                'net' => '6.25',
                'tax' => 3.24,
            ],
            'list_price' => [
                'gross' => 12.45,
                'net' => '6.25',
                'tax' => 3.24,
            ],
        ],
    ];

    public function test_it_can_return_its_country()
    {
        $product = $this->product();

        $this->assertSame('BE', $product->customerCountry());
    }

    public function test_it_can_return_its_currency()
    {
        $currency = $this->product()->currency();

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertSame('EUR', $currency->getCode());
    }

    public function test_it_can_return_prices()
    {
        $product = $this->product();

        $this->assertInstanceOf(Price::class, $product->price());
        $this->assertInstanceOf(Price::class, $product->listPrice());
        $this->assertInstanceOf(Price::class, $product->initialPrice());
        $this->assertInstanceOf(Price::class, $product->initialListPrice());
        $this->assertInstanceOf(Price::class, $product->recurringPrice());
        $this->assertInstanceOf(Price::class, $product->recurringListPrice());
    }

    public function test_it_can_return_plan_info()
    {
        $product = $this->product();

        $this->assertSame(5, $product->planTrialDays());
        $this->assertSame('monthly', $product->planInterval());
        $this->assertSame(1, $product->planFrequency());
    }

    public function test_it_implements_arrayable_and_jsonable()
    {
        $product = $this->product();
        $data = self::DEFAULTS;

        $this->assertSame($data, $product->toArray());
        $this->assertSame($data, $product->jsonSerialize());
        $this->assertSame(json_encode($data), $product->toJson());
    }

    public function test_it_can_check_if_has_tax()
    {
        $product = $this->product();

        $this->assertTrue($product->price()->hasTax());
    }

    /**
     * Get a test product price object.
     *
     * @param  array  $product
     * @return \Laravel\Paddle\ProductPrice
     */
    private function product(array $product = [])
    {
        return new ProductPrice('BE', array_merge(self::DEFAULTS, $product));
    }
}
