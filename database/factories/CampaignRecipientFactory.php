<?php

namespace Database\Factories;

use App\Models\CampaignRecipient;
use App\Models\MarketingCampaign;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignRecipientFactory extends Factory
{
    protected $model = CampaignRecipient::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'campaign_id' => MarketingCampaign::factory(),
            'contact_id' => Contact::factory(),
            'email' => $this->faker->email,
            'status' => $this->faker->randomElement(['pending', 'sent', 'opened', 'clicked', 'bounced']),
            'sent_at' => $this->faker->optional()->dateTimeThisMonth(),
            'opened_at' => $this->faker->optional()->dateTimeThisMonth(),
            'clicked_at' => $this->faker->optional()->dateTimeThisMonth(),
            'bounced_at' => $this->faker->optional()->dateTimeThisMonth(),
            'metadata' => json_encode([
                'ip_address' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'location' => $this->faker->city
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}