<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'reminder_date' => $this->faker->optional()->dateTimeBetween('now', '+7 days'),
            'reminder_sent' => false,
            'google_event_id' => $this->faker->optional()->uuid(),
            'outlook_event_id' => $this->faker->optional()->uuid(),
            'calendar_type' => $this->faker->optional()->randomElement(['google', 'outlook']),
            'assigned_to' => User::factory(),
            'team_id' => Team::factory(),
            'contact_id' => null,
            'lead_id' => null,
            'company_id' => null,
            'opportunity_id' => null,
        ];
    }

    public function withContact()
    {
        return $this->state(function (array $attributes) {
            return [
                'contact_id' => Contact::factory(),
                'lead_id' => null,
            ];
        });
    }

    public function withLead()
    {
        return $this->state(function (array $attributes) {
            return [
                'lead_id' => Lead::factory(),
                'contact_id' => null,
            ];
        });
    }

    public function withCompany()
    {
        return $this->state(function (array $attributes) {
            return ['company_id' => Company::factory()];
        });
    }

    public function withOpportunity()
    {
        return $this->state(function (array $attributes) {
            return ['opportunity_id' => Opportunity::factory()];
        });
    }

    public function withReminder()
    {
        return $this->state(function (array $attributes) {
            return [
                'reminder_date' => $this->faker->dateTimeBetween('now', '+7 days'),
                'reminder_sent' => false,
            ];
        });
    }

    public function reminderDue()
    {
        return $this->state(function (array $attributes) {
            return [
                'reminder_date' => now()->subMinutes(5),
                'reminder_sent' => false,
            ];
        });
    }
}