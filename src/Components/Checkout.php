<?php

namespace Laravel\Paddle\Components;

use Illuminate\View\Component;
use Laravel\Paddle\Checkout as PaddleCheckout;

class Checkout extends Component
{
    /**
     * Initialise the Checkout component class.
     */
    public function __construct(
        protected PaddleCheckout $checkout,
        public string $id = 'paddle-checkout-container',
        protected int $height = 366,
        protected array $settings = []
    ) {
        //
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('cashier::components.checkout');
    }

    /**
     * Get the options for the inline Paddle Checkout script.
     *
     * @return array
     */
    public function options()
    {
        $options = [
            'settings' => array_filter(array_merge([
                'displayMode' => 'inline',
                'frameTarget' => $this->id,
                'frameInitialHeight' => $this->height,
                'frameStyle' => 'width: 100%; background-color: transparent; border: none;',
                'successUrl' => $this->checkout->getReturnUrl(),
            ], $this->settings)),
            'items' => $this->checkout->getItems(),
        ];

        if ($customer = $this->checkout->getCustomer()) {
            $options['customer'] = ['id' => $customer->paddle_id];
        }

        return $options;
    }
}
