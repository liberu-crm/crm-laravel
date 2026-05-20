<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'status'              => $this->faker->randomElement(['new', 'contacted', 'qualified', 'lost']),
            'source'              => $this->faker->randomElement(['website', 'referral', 'social_media']),
            'potential_value'     => $this->faker->randomFloat(2, 1000, 100000),
            'expected_close_date' => $this->faker->dateTimeThisYear()->format('Y-m-d'),
            'lifecycle_stage'     => $this->faker->randomElement(['lead', 'marketing_qualified_lead', 'sales_qualified_lead']),
            'score'               => $this->faker->numberBetween(0, 100),
            'custom_fields'       => null,
        ];
    }
}
