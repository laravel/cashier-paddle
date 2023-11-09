<?php

namespace Tests\Unit;

use Laravel\Paddle\Price;
use Laravel\Paddle\PricePreview;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class PricePreviewTest extends TestCase
{
    const DEFAULTS = [
        'formatted_totals' => [
            'total' => '€12.45',
            'tax' => '€2.45',
        ],
        'totals' => [
            'total' => '1245',
            'tax' => '245',
        ],
        'price' => [
            'unit_price' => [
                'amount' => '1245',
                'currency_code' => 'EUR',
            ],
            'billing_cycle' => [
                'interval' => 'monthly',
                'frequency' => 1,
            ],
        ],
    ];

    public function test_it_can_return_its_currency()
    {
        $currency = $this->preview()->currency();

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertSame('EUR', $currency->getCode());
    }

    public function test_it_can_return_its_price()
    {
        $preview = $this->preview();

        $this->assertInstanceOf(Price::class, $preview->price());
    }

    public function test_it_can_return_price_info()
    {
        $preview = $this->preview();

        $this->assertSame('monthly', $preview->price()->interval());
        $this->assertSame(1, $preview->price()->frequency());
    }

    public function test_it_implements_arrayable_and_jsonable()
    {
        $preview = $this->preview();
        $data = self::DEFAULTS;

        $this->assertSame($data, $preview->toArray());
        $this->assertSame($data, $preview->jsonSerialize());
        $this->assertSame(json_encode($data), $preview->toJson());
    }

    public function test_it_can_check_if_has_tax()
    {
        $preview = $this->preview();

        $this->assertTrue($preview->hasTax());
    }

    /**
     * Get a test price preview object.
     *
     * @param  array  $product
     * @return \Laravel\Paddle\PricePreview
     */
    private function preview(array $preview = [])
    {
        return new PricePreview(array_merge(self::DEFAULTS, $preview));
    }
}
