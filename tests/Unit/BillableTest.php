<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\User;

class BillableTest extends TestCase
{
    public function test_it_can_get_its_paddle_id()
    {
        $billable = new User(['paddle_id' => 5]);

        $this->assertSame(5, $billable->paddleId());
    }

    public function test_it_can_determine_if_it_has_a_paddle()
    {
        $billable = new User(['paddle_id' => 5]);

        $this->assertTrue($billable->hasPaddleId());
    }

    public function test_it_can_determine_if_it_has_no_paddle_id()
    {
        $billable = new User;

        $this->assertFalse($billable->hasPaddleId());
    }
}
