<?php

namespace Database\Factories;

use App\Models\Email;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailFactory extends Factory
{
    protected $model = Email::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(3, true),
            'from' => $this->faker->email,
            'to' => json_encode([$this->faker->email]),
            'cc' => json_encode($this->faker->optional()->array(['email' => $this->faker->email])),
            'bcc' => json_encode($this->faker->optional()->array(['email' => $this->faker->email])),
            'status' => $this->faker->randomElement(['draft', 'sent', 'scheduled', 'failed']),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('now', '+1 week'),
            'sent_at' => $this->faker->optional()->dateTimeThisMonth(),
            'opened_at' => $this->faker->optional()->dateTimeThisMonth(),
            'clicked_at' => $this->faker->optional()->dateTimeThisMonth(),
            'email_template_id' => $this->faker->optional()->uuid,
            'campaign_id' => $this->faker->optional()->uuid,
            'metadata' => json_encode([
                'ip' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'attachments' => []
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}