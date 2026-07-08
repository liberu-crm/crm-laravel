<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Support\AccessContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldMaskingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $this->contact = Contact::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '+15551234567',
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

        $array = $this->contact->fresh()->toArray();

        $this->assertSame('[hidden]', $array['email']);
        $this->assertSame('[hidden]', $array['phone_number']);
        $this->assertSame('Jane', $array['name']);
    }

    public function test_manager_sees_real_values(): void
    {
        $this->actAs('manager');

        $array = $this->contact->fresh()->toArray();

        $this->assertSame('jane@example.com', $array['email']);
        $this->assertSame('+15551234567', $array['phone_number']);
    }

    public function test_masking_does_not_mutate_the_real_attribute(): void
    {
        $this->actAs('free');

        // Direct access (business logic, saves) still sees the real value.
        $this->assertSame('jane@example.com', $this->contact->fresh()->email);
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
