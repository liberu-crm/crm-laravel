<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Contact;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use App\Support\AccessContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TerritoryScopingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Territory $t1;

    private Territory $t2;

    private Contact $inT1;

    private Contact $inT2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $this->t1 = Territory::factory()->create(['team_id' => $this->team->id]);
        $this->t2 = Territory::factory()->create(['team_id' => $this->team->id]);
        $this->inT1 = Contact::factory()->create(['team_id' => $this->team->id, 'territory_id' => $this->t1->id]);
        $this->inT2 = Contact::factory()->create(['team_id' => $this->team->id, 'territory_id' => $this->t2->id]);
    }

    /** Contacts visible ignoring the team scope (isolate the territory scope). */
    private function visibleContactIds(): Collection
    {
        return Contact::withoutGlobalScope('tenant')->pluck('id');
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($user);
        setPermissionsTeamId($this->team->id);
        $user->assignRole($role);

        return $user;
    }

    public function test_sales_rep_sees_only_their_territory(): void
    {
        $rep = $this->member('sales_rep');
        $rep->territories()->attach($this->t1);
        $this->actingAs($rep);

        $ids = $this->visibleContactIds();
        $this->assertTrue($ids->contains($this->inT1->id));
        $this->assertFalse($ids->contains($this->inT2->id));
    }

    public function test_manager_sees_all_territories(): void
    {
        $this->actingAs($this->member('manager'));

        $ids = $this->visibleContactIds();
        $this->assertTrue($ids->contains($this->inT1->id));
        $this->assertTrue($ids->contains($this->inT2->id));
    }

    public function test_roleless_user_is_unrestricted(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($user);
        $this->actingAs($user);

        $ids = $this->visibleContactIds();
        $this->assertTrue($ids->contains($this->inT1->id));
        $this->assertTrue($ids->contains($this->inT2->id));
    }

    public function test_restricted_territory_ids_for_a_sales_rep(): void
    {
        $rep = $this->member('sales_rep');
        $rep->territories()->attach($this->t1);
        $this->actingAs($rep);

        $this->assertEqualsCanonicalizing([$this->t1->id], AccessContext::restrictedTerritoryIds());
    }

    public function test_restricted_territory_ids_is_null_for_a_manager(): void
    {
        $this->actingAs($this->member('manager'));

        $this->assertNull(AccessContext::restrictedTerritoryIds());
    }
}
