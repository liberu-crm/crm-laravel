<?php

namespace Database\Factories;

use App\Models\AccountingIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingIntegrationFactory extends Factory
{
    protected $model = AccountingIntegration::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'provider' => $this->faker->randomElement(['quickbooks', 'xero', 'sage']),
            'api_key' => $this->faker->uuid,
            'api_secret' => $this->faker->sha256,
            'status' => 'active',
            'settings' => json_encode([
                'auto_sync' => true,
                'sync_frequency' => 'daily'
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}