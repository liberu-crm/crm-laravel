<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SsoConnection;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class SsoConnectionFactory extends Factory
{
    protected $model = SsoConnection::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'provider' => 'oidc',
            'client_id' => $this->faker->uuid(),
            'client_secret' => 'secret-'.$this->faker->uuid(),
            'issuer_url' => 'https://idp.example.com',
            'enabled' => false,
            'allow_jit' => false,
            'allowed_domain' => null,
            'require_sso' => false,
            'token_auth_method' => 'client_secret_post',
            'role_mappings' => null,
        ];
    }
}
