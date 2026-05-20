<?php

namespace Database\Factories;

use App\Models\KnowledgeBaseArticle;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeBaseArticleFactory extends Factory
{
    protected $model = KnowledgeBaseArticle::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'title' => $this->faker->sentence(),
            'slug' => $this->faker->slug(),
            'content' => $this->faker->paragraphs(5, true),
            'excerpt' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['getting-started', 'features', 'troubleshooting', 'faq']),
            'tags' => json_encode($this->faker->words(3)),
            'author_id' => Team::factory(),
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'published_at' => $this->faker->optional()->dateTimeThisYear(),
            'view_count' => $this->faker->numberBetween(0, 10000),
            'helpful_count' => $this->faker->numberBetween(0, 1000),
            'not_helpful_count' => $this->faker->numberBetween(0, 100),
            'meta_title' => $this->faker->optional()->sentence(),
            'meta_description' => $this->faker->optional()->paragraph(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}