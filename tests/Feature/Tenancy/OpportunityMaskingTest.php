<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityMaskingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $this->opportunity = Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'deal_size' => 50000,
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

    public function test_free_user_sees_masked_deal_size_in_serialization(): void
    {
        $this->actAs('free');

        $array = $this->opportunity->fresh()->toArray();

        $this->assertSame('[hidden]', $array['deal_size']);
    }

    public function test_manager_sees_real_deal_size(): void
    {
        $this->actAs('manager');

        $array = $this->opportunity->fresh()->toArray();

        $this->assertEquals(50000, $array['deal_size']);
    }

    public function test_masking_does_not_mutate_the_real_attribute(): void
    {
        $this->actAs('free');

        // Direct access (business logic, saves) still sees the real value.
        $this->assertEquals(50000, $this->opportunity->fresh()->deal_size);
    }
}
