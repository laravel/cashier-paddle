<?php

namespace Laravel\Paddle;

class Checkout
{
    /**
     * The return url which will be triggered upon starting the subscription.
     */
    protected string $returnTo = null;

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
     * The return url to the success page.
     */
    public function returnTo(string $returnTo): self
    {
        $this->returnTo = $returnTo;

        return $this;
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
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * Return the return url for the checkout.
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnTo;
    }
}
