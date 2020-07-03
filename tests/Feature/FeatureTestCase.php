<?php

namespace Tests\Feature;

use Laravel\Paddle\CashierServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\User;

abstract class FeatureTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->artisan('migrate')->run();
    }

    protected function createBillable($description = 'taylor', array $options = []): User
    {
        $user =$this->createUser($description);

        $user->customer()->create(array_merge([
            'paddle_id' => $_SERVER['PADDLE_TEST_CUSTOMER_ID'],
            'paddle_email' => $_SERVER['PADDLE_TEST_CUSTOMER_EMAIL'],
        ], $options));

        return $user;
    }

    protected function createUser($description = 'taylor', array $options = []): User
    {
        return User::create(array_merge([
            'email' => "{$description}@paddle-test.com",
            'name' => 'Taylor Otwell',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ], $options));
    }

    protected function getPackageProviders($app)
    {
        return [CashierServiceProvider::class];
    }
}
