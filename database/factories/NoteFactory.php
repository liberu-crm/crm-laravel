<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        // Only `content` is NOT NULL. Note is NOT polymorphic — contact_id/
        // company_id/opportunity_id are plain nullable ints (no FK constraint).
        return [
            'content' => $this->faker->paragraph(),
            'contact_id' => Contact::factory(),
        ];
    }
}
