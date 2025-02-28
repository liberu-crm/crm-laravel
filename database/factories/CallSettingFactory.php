<?php

namespace Database\Factories;

use App\Models\CallSetting;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class CallSettingFactory extends Factory
{
    protected $model = CallSetting::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'provider' => $this->faker->randomElement(['twilio', 'vonage', 'ringcentral']),
            'api_key' => $this->faker->uuid,
            'api_secret' => $this->faker->sha256,
            'from_number' => $this->faker->e164PhoneNumber,
            'settings' => json_encode([
                'recording_enabled' => $this->faker->boolean,
                'voicemail_enabled' => $this->faker->boolean,
                'transcription_enabled' => $this->faker->boolean,
                'call_forwarding' => [
                    'enabled' => $this->faker->boolean,
                    'number' => $this->faker->phoneNumber
                ]
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}