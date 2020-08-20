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
        $user = $this->createUser($description);

        $user->createAsCustomer($options);

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

    protected function updateUrl($subscriptionId = 1)
    {
        return "https://checkout.paddle.com/subscription/update?user=1&subscription={$subscriptionId}&hash=114493d1810c2dcd45c5cd44d16c3d8484082360";
    }

    protected function getPackageProviders($app)
    {
        return [CashierServiceProvider::class];
    }
}
