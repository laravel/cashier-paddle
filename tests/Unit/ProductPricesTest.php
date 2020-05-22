<?php

namespace Tests\Unit;

use Laravel\Paddle\ProductPrices;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class ProductPricesTest extends TestCase
{
    public function it_can_return_its_country()
    {
        $product = $this->product();

        $this->assertInstanceOf('BE', $product->customerCountry());
    }

    public function it_can_return_its_currency()
    {
        $currency = $this->product()->currency();

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertInstanceOf('EUR', $currency->getCode());
    }

    public function test_it_implements_arrayable_and_jsonable()
    {
        $product = $this->product();
        $data = [
            'product_id' => 232,
            'product_title' => 232,
            'currency' => 'EUR',
        ];

        $this->assertSame($data, $product->toArray());
        $this->assertSame($data, $product->jsonSerialize());
        $this->assertSame(json_encode($data), $product->toJson());
    }

    /**
     * Get a test product object.
     *
     * @param  array  $product
     * @return \Laravel\Paddle\ProductPrices
     */
    private function product(array $product = [])
    {
        return new ProductPrices('BE', array_merge([
            'product_id' => 232,
            'product_title' => 232,
            'currency' => 'EUR',
        ], $product));
    }
}
