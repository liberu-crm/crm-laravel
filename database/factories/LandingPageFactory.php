<?php

namespace Database\Factories;

use App\Models\LandingPage;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class LandingPageFactory extends Factory
{
    protected $model = LandingPage::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug(),
            'template' => $this->faker->randomElement(['default', 'modern', 'minimal']),
            'content' => json_encode([
                'hero' => [
                    'title' => $this->faker->sentence(),
                    'subtitle' => $this->faker->paragraph(),
                    'cta_text' => $this->faker->words(3, true),
                    'background_image' => $this->faker->imageUrl()
                ],
                'sections' => [
                    [
                        'type' => 'features',
                        'items' => $this->faker->paragraphs(3)
                    ],
                    [
                        'type' => 'testimonials',
                        'items' => $this->faker->paragraphs(2)
                    ]
                ]
            ]),
            'settings' => json_encode([
                'meta_title' => $this->faker->sentence(),
                'meta_description' => $this->faker->paragraph(),
                'custom_css' => '',
                'custom_js' => '',
                'tracking_code' => $this->faker->optional()->uuid
            ]),
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'published_at' => $this->faker->optional()->dateTimeThisYear(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}