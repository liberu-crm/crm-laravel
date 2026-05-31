<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'team_id' => Team::factory(),
            'auditable_type' => $this->faker->randomElement([\App\Models\Lead::class, \App\Models\Contact::class, \App\Models\Deal::class]),
            'auditable_id' => $this->faker->numberBetween(1, 100),
            'event' => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'old_values' => json_encode([]),
            'new_values' => json_encode([
                'name' => $this->faker->name,
                'status' => $this->faker->word,
            ]),
            'url' => $this->faker->url,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'created_at' => $this->faker->dateTimeThisYear(),
            'updated_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
