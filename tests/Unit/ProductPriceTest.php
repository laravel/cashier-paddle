<?php

namespace Tests\Unit;

use Laravel\Paddle\ProductPrice;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
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
     * Get a test product price object.
     *
     * @param  array  $product
     * @return \Laravel\Paddle\ProductPrice
     */
    private function product(array $product = [])
    {
        return new ProductPrice('BE', array_merge([
            'product_id' => 232,
            'product_title' => 232,
            'currency' => 'EUR',
        ], $product));
    }
}
