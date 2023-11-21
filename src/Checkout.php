<?php

namespace Laravel\Paddle;

use LogicException;

class Checkout
{
    /**
     * The custom data for the checkout.
     */
    protected array $custom = [];

    /**
     * The URL which the customer will be returned to after starting the subscription.
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
     * Add custom data to the checkout.
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
     * Convert the checkout to an array compatible with `Paddle.Checkout.open`.
     */
    public function options(): array
    {
        $options = [
            'settings' => array_filter([
                'displayMode' => 'inline',
                'frameStyle' => 'width: 100%; background-color: transparent; border: none;',
                'successUrl' => $this->returnTo,
            ]),
            'items' => $this->items,
        ];

        if ($customer = $this->customer) {
            $options['customer'] = ['id' => $customer->paddle_id];
        }

        if ($custom = $this->custom) {
            $options['customData'] = $custom;
        }

        return $options;
    }

    /**
     * The URL the customer should be returned to after a successful checkout.
     */
    public function returnTo(string $returnTo): self
    {
        $this->returnTo = $returnTo;

        return $this;
    }

    /**
     * Get the customer for the checkout.
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * Get the items for the checkout.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get the custom data for the checkout.
     */
    public function getCustomData(): array
    {
        return $this->custom;
    }

    /**
     * Get the URL the customer should be returned to after a successful checkout.
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnTo;
    }
}
