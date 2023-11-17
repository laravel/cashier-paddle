<?php

namespace Laravel\Paddle;

use LogicException;

class Checkout
{
    /**
     * Custom data for the checkout.
     */
    protected array $custom = [];

    /**
     * The return url which will be triggered upon starting the subscription.
     */
    protected ?string $returnTo = null;

    /**
     * Create a new checkout instance.
     */
    public function __construct(protected ?Customer $customer, protected array $items = [])
    {
        $this->items = Cashier::normalizeItems($items, 'priceId');
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
     * Add custom data to a checkout.
     */
    public function customData(array $custom): self
    {
        // Make sure subscription_type doesn't gets unset.
        if (isset($this->custom['subscription_type']) && isset($custom['subscription_type'])) {
            throw new LogicException('The subscription_type can not be overwritten.');
        }

        $this->custom = $custom;

        return $this;
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
     * Return the custom data for the checkout.
     */
    public function getCustom(): array
    {
        return $this->custom;
    }

    /**
     * Return the return url for the checkout.
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnTo;
    }
}
