<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadMaskingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $this->lead = Lead::factory()->create([
            'team_id' => $this->team->id,
            'potential_value' => 12345.67,
        ]);
    }

    private function actAs(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($user);
        setPermissionsTeamId($this->team->id);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_free_user_sees_masked_potential_value_in_serialization(): void
    {
        $this->actAs('free');

        $array = $this->lead->fresh()->toArray();

        $this->assertSame('[hidden]', $array['potential_value']);
    }

    public function test_manager_sees_real_potential_value(): void
    {
        $this->actAs('manager');

        $array = $this->lead->fresh()->toArray();

        $this->assertSame('12345.67', $array['potential_value']);
    }

    public function test_masking_does_not_mutate_the_real_attribute(): void
    {
        $this->actAs('free');

        // Direct access (business logic, saves) still sees the real value.
        $this->assertSame('12345.67', $this->lead->fresh()->potential_value);
    }
}
