<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    
    public function run(): void
    {
        Opportunity::factory(10)->create();
    }
}
