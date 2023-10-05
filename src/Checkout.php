<?php

namespace Laravel\Paddle;

class Checkout
{
    /**
     * Create a new checkout instance.
     */
    public function __construct(protected ?Customer $customer, protected array $items = [])
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
        })->values()->all();
    }

    /**
     * Create a new checkout instance for a guest.
     */
    public static function guest(array $items = []): self
    {
        return new static(null, $items);
    }

    /**
     * Create a new checkout instance for an existing customer.
     */
    public static function customer(Customer $customer, array $items = []): self
    {
        return new static($customer, $items);
    }

    /**
     * Return the items for the checkout.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Return the customer for the checkout.
     *
     * @return \Laravel\Paddle\Customer|null
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }
}
