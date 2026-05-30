<?php

namespace Database\Factories;

use App\Models\EmailTracking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmailTrackingFactory extends Factory
{
    protected $model = EmailTracking::class;

    public function definition(): array
    {
        return [
            'tracking_id' => (string) Str::uuid(),
        ];
    }
}
