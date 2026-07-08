<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use App\Support\AccessContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyMaskingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        // CompanyFactory does not set annual_revenue, so pass it explicitly.
        $this->company = Company::factory()->create([
            'team_id' => $this->team->id,
            'phone_number' => '+15551234567',
            'annual_revenue' => 5000000,
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

    public function test_free_user_sees_masked_sensitive_fields_in_serialization(): void
    {
        $this->actAs('free');

        $array = $this->company->fresh()->toArray();

        $this->assertSame('[hidden]', $array['phone_number']);
        $this->assertSame('[hidden]', $array['annual_revenue']);
    }

    public function test_manager_sees_real_values(): void
    {
        $this->actAs('manager');

        $array = $this->company->fresh()->toArray();

        $this->assertSame('+15551234567', $array['phone_number']);
        // annual_revenue is an uncast decimal column; its serialized format is
        // driver-dependent (MySQL "5000000.00" vs SQLite 5000000), so compare
        // loosely — the point is it is the real value, not the mask.
        $this->assertEquals(5000000, $array['annual_revenue']);
    }

    public function test_masking_does_not_mutate_the_real_attribute(): void
    {
        $this->actAs('free');

        // Direct access (business logic, saves) still sees the real value.
        $this->assertSame('+15551234567', $this->company->fresh()->phone_number);
    }

    public function test_should_mask_fields_is_true_for_free_only(): void
    {
        $this->actAs('free');
        $this->assertTrue(AccessContext::shouldMaskFields());
    }

    public function test_should_mask_fields_is_false_for_a_manager(): void
    {
        $this->actAs('manager');
        $this->assertFalse(AccessContext::shouldMaskFields());
    }
}
