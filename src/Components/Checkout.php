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
        $options = $this->checkout->options();

        $options['settings']['frameTarget'] = $this->id;
        $options['settings']['frameInitialHeight'] = $this->height;

        unset($options['settings']['successUrl']);

        return array_filter(array_merge($options['settings'], $this->settings));
    }
}
