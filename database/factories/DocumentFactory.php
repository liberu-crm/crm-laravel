<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->words(3, true),
            'file_name' => $this->faker->uuid . '.' . $this->faker->fileExtension(),
            'file_path' => 'documents/' . $this->faker->uuid,
            'file_size' => $this->faker->numberBetween(1024, 10485760),
            'mime_type' => $this->faker->mimeType(),
            'type' => $this->faker->randomElement(['contract', 'proposal', 'invoice', 'other']),
            'documentable_type' => $this->faker->randomElement(['App\\Models\\Deal', 'App\\Models\\Contact', 'App\\Models\\Company']),
            'documentable_id' => $this->faker->numberBetween(1, 100),
            'description' => $this->faker->optional()->sentence,
            'tags' => json_encode($this->faker->words(3)),
            'status' => $this->faker->randomElement(['draft', 'final', 'archived']),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}