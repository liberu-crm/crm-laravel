<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Fortify\CreateNewUserWithTeams;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FortifyActionsTest extends TestCase
{
    use RefreshDatabase;

    // --- CreateNewUserWithTeams (registration) --------------------------------

    public function test_register_creates_user_with_hashed_password_personal_team_and_admin_role(): void
    {
        $user = app(CreateNewUserWithTeams::class)->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.com',
            'name' => 'Ada Lovelace',
        ]);

        // Password is stored hashed (not plaintext, not double-hashed) and verifies.
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));

        // Registration gives the creator an owned personal team + a current team.
        $this->assertNotNull($user->current_team_id);
        $personalTeam = $user->ownedTeams()->where('personal_team', true)->first();
        $this->assertNotNull($personalTeam, 'Registration should create a personal team.');
        // Exactly one team — guards the fixed double-team-creation bug.
        $this->assertCount(1, $user->ownedTeams);
        $this->assertSame($personalTeam->id, $user->current_team_id);

        // The creator holds the admin role in the team they are acting in.
        setPermissionsTeamId($user->current_team_id);
        $this->assertTrue($user->fresh()->hasRole(Role::Admin));
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@example.com']);

        $this->assertRegistrationFails([
            'name' => 'Grace',
            'email' => 'dupe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], 'email');

        // Only the pre-existing user exists; registration did not add a second.
        $this->assertSame(1, User::query()->where('email', 'dupe@example.com')->count());
    }

    public function test_register_rejects_mismatched_password_confirmation(): void
    {
        $this->assertRegistrationFails([
            'name' => 'Grace',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ], 'password');

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.com']);
    }

    public function test_register_rejects_weak_password(): void
    {
        $this->assertRegistrationFails([
            'name' => 'Grace',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ], 'password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    private function assertRegistrationFails(array $input, string $field): void
    {
        try {
            app(CreateNewUserWithTeams::class)->create($input);
            $this->fail("Expected registration to fail validation for [{$field}].");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    // --- ResetUserPassword ----------------------------------------------------

    public function test_reset_password_sets_new_hashed_password(): void
    {
        $user = User::factory()->create();

        app(ResetUserPassword::class)->reset($user, [
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ]);

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
    }

    // --- UpdateUserPassword ---------------------------------------------------

    public function test_update_password_changes_password_when_current_matches(): void
    {
        // UserFactory seeds password = 'password'.
        $user = User::factory()->create();
        $this->actingAs($user); // current_password:web validates against the logged-in user

        app(UpdateUserPassword::class)->update($user, [
            'current_password' => 'password',
            'password' => 'brand-new-1',
            'password_confirmation' => 'brand-new-1',
        ]);

        $this->assertTrue(Hash::check('brand-new-1', $user->fresh()->password));
    }

    public function test_update_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        try {
            app(UpdateUserPassword::class)->update($user, [
                'current_password' => 'not-the-password',
                'password' => 'brand-new-1',
                'password_confirmation' => 'brand-new-1',
            ]);
            $this->fail('Expected validation to fail for a wrong current password.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('current_password', $e->errors());
        }

        // Password is unchanged.
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    // --- UpdateUserProfileInformation -----------------------------------------

    public function test_update_profile_information_updates_name_and_email(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'email' => 'old@example.com']);

        app(UpdateUserProfileInformation::class)->update($user, [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_update_profile_information_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'me@example.com']);

        try {
            app(UpdateUserProfileInformation::class)->update($user, [
                'name' => 'Me',
                'email' => 'taken@example.com',
            ]);
            $this->fail('Expected validation to fail for a duplicate email.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }

        $this->assertSame('me@example.com', $user->fresh()->email);
    }
}
