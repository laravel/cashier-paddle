<?php

namespace Laravel\Paddle;

class Checkout
{
    /**
     * The return url to the success page.
     *
     * @var string
     */
    public $returnTo;

    /**
     * Create a new checkout instance.
     */
    public function __construct(protected array $items = [])
    {
        $this->items = collect($items)->map(function ($item, $key) {
            if (is_array($item)) {
                return $item;
            }

            if (is_string($key)) {
                return [
                    'priceId' => $key,
                    'quantity' => $item,
                ];
            }

            return [
                'priceId' => $item,
                'quantity' => 1,
            ];
        })->all();
    }

    /**
     * Create a new checkout instance.
     */
    public static function make(array $items = []): self
    {
        return new static($items);
    }

    /**
     * The return url which will be triggered upon starting the subscription.
     *
     * @param  string  $returnTo
     * @param  string  $checkoutParameter
     * @return $this
     */
    public function returnTo($returnTo)
    {
        $this->returnTo = $returnTo;

        return $this;
    }

    /**
     * Return the items for the checkout.
     *
     * @return array
     */
    public function items(): array
    {
        return $this->items;
    }
}
