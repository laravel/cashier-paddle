<?php

namespace Laravel\Paddle\Components;

use Illuminate\View\Component;

class Checkout extends Component
{
    /**
     * The identifier for the Paddle checkout script and container.
     *
     * @var string
     */
    public $id;

    /**
     * The initial height of the inline checkout.
     *
     * @var int
     */
    public $height;

    /**
     * The options for the inline Paddle Checkout script.
     *
     * @var array
     */
    protected $options;

    /**
     * Initialise the Checkout component class.
     *
     * @param  string  $override
     * @param  string  $id
     * @param  int  $height
     * @param  array  $options
     * @return void
     */
    public function __construct(string $override = '', $id = 'paddle-checkout', int $height = 366, array $options = [])
    {
        $this->id = $id;
        $this->height = $height;
        $this->options = $override ? ['override' => $override] : $options;
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
        return array_merge([
            'method' => 'inline',
            'frameTarget' => $this->id,
            'frameInitialHeight' => $this->height,
            'frameStyle' => 'width: 100%; background-color: transparent; border: none;',
        ], $this->options);
    }
}
