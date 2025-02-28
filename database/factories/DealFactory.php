<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Team;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\Contact;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'pipeline_id' => Pipeline::factory(),
            'stage_id' => Stage::factory(),
            'contact_id' => Contact::factory(),
            'company_id' => Company::factory(),
            'name' => $this->faker->catchPhrase,
            'value' => $this->faker->randomFloat(2, 1000, 1000000),
            'currency' => $this->faker->currencyCode,
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'probability' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['open', 'won', 'lost']),
            'source' => $this->faker->randomElement(['website', 'referral', 'cold_call', 'exhibition']),
            'notes' => $this->faker->paragraph,
            'lost_reason' => $this->faker->optional()->sentence,
            'custom_fields' => json_encode([
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'deal_type' => $this->faker->randomElement(['new_business', 'existing_business', 'renewal'])
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}