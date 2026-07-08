<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Campaign;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignMaskingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $this->campaign = Campaign::factory()->create([
            'team_id' => $this->team->id,
            'budget' => 12345.67,
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

    public function test_free_user_sees_masked_budget_in_serialization(): void
    {
        $this->actAs('free');

        $array = $this->campaign->fresh()->toArray();

        $this->assertSame('[hidden]', $array['budget']);
    }

    public function test_manager_sees_real_budget(): void
    {
        $this->actAs('manager');

        $array = $this->campaign->fresh()->toArray();

        $this->assertSame('12345.67', $array['budget']);
    }

    public function test_masking_does_not_mutate_the_real_attribute(): void
    {
        $this->actAs('free');

        // Direct access (business logic, saves) still sees the real value.
        $this->assertSame('12345.67', $this->campaign->fresh()->budget);
    }
}
