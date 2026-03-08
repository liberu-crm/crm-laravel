<?php

namespace Database\Factories;

use App\Models\AccountingIntegration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingIntegrationFactory extends Factory
{
    protected $model = AccountingIntegration::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'platform' => $this->faker->randomElement(['quickbooks', 'xero', 'sage']),
            'connection_details' => [
                'api_key' => $this->faker->uuid,
                'api_secret' => $this->faker->sha256,
                'auto_sync' => true,
                'sync_frequency' => 'daily',
            ],
            'last_synced' => null,
        ];
    }
}
