<?php

namespace Tests\Unit;

use Laravel\Paddle\Checkout;
use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    public function test_inline_checkout_does_not_allow_logout()
    {
        $options = Checkout::guest(['pri_123'])->options();

        $this->assertFalse($options['settings']['allowLogout']);
        $this->assertArrayNotHasKey('successUrl', $options['settings']);
    }
}
