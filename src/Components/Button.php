<?php

namespace Laravel\Paddle\Components;

use Illuminate\View\Component;
use Laravel\Paddle\Checkout as PaddleCheckout;

class Button extends Component
{
    /**
     * Initialise the Button component class.
     */
    public function __construct(public PaddleCheckout $checkout)
    {
        //
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('cashier::components.button');
    }
}
