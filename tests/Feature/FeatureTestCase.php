<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Paddle\Cashier;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\User;

abstract class FeatureTestCase extends TestCase
{
    use LazilyRefreshDatabase, WithWorkbench;

    protected function createBillable($description = 'taylor', array $options = []): User
    {
        $user = $this->createUser($description);

        Cashier::fake([
            'customers*' => [
                'data' => [[
                    'id' => 'cus_123456789',
                    'name' => $user->name,
                    'email' => $user->email,
                ]],
            ],
        ]);

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
}
