<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeBaseArticle;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeBaseArticleFactory extends Factory
{
    protected $model = KnowledgeBaseArticle::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        // Only real columns — the table is title/content/category/team_id plus the
        // portal fields added in 2026_07_07. The prior factory wrote ~10 phantom
        // columns (slug/status/author_id/…) that do not exist and broke inserts.
        return [
            'team_id' => Team::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(5, true),
            'category' => $this->faker->randomElement(['getting-started', 'features', 'troubleshooting', 'faq']),
            'is_published' => true,
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ];
    }
}
