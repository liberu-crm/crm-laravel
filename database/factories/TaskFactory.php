<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        // Keep reminder_date on/before due_date so consumers that enforce
        // reminder <= due (e.g. TaskForm) get self-consistent data.
        $dueDate = $this->faker->dateTimeBetween('now', '+30 days');

        return [
            'name' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'due_date' => $dueDate,
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'reminder_date' => $this->faker->optional()->dateTimeBetween('now', $dueDate),
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
        return $this->state(fn(array $attributes) => [
            'contact_id' => Contact::factory(),
            'lead_id' => null,
        ]);
    }

    public function withLead()
    {
        return $this->state(fn(array $attributes) => [
            'lead_id' => Lead::factory(),
            'contact_id' => null,
        ]);
    }

    public function withCompany()
    {
        return $this->state(fn(array $attributes) => ['company_id' => Company::factory()]);
    }

    public function withOpportunity()
    {
        return $this->state(fn(array $attributes) => ['opportunity_id' => Opportunity::factory()]);
    }

    public function withReminder()
    {
        return $this->state(fn(array $attributes) => [
            'reminder_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'reminder_sent' => false,
        ]);
    }

    public function reminderDue()
    {
        return $this->state(fn(array $attributes) => [
            'reminder_date' => now()->subMinutes(5),
            'reminder_sent' => false,
        ]);
    }
}
