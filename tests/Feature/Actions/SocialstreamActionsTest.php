<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Socialstream\CreateConnectedAccount;
use App\Actions\Socialstream\CreateUserWithTeamsFromProvider;
use App\Actions\Socialstream\HandleInvalidState;
use App\Actions\Socialstream\SetUserPassword;
use App\Actions\Socialstream\UpdateConnectedAccount;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JoelButcher\Socialstream\Events\ConnectedAccountCreated;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class SocialstreamActionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a fake Socialite provider user. getId/getName/getEmail/getNickname/
     * getAvatar come from the contract; token/refreshToken/expiresIn are public
     * properties the actions read directly (as on the real Socialite\Two\User).
     */
    private function fakeProviderUser(array $o = []): ProviderUser
    {
        $user = Mockery::mock(ProviderUser::class);
        $user->shouldReceive('getId')->andReturn($o['id'] ?? '1234567890');
        $user->shouldReceive('getName')->andReturn($o['name'] ?? 'Ada Lovelace');
        $user->shouldReceive('getEmail')->andReturn($o['email'] ?? 'ada@example.com');
        $user->shouldReceive('getNickname')->andReturn($o['nickname'] ?? 'ada');
        $user->shouldReceive('getAvatar')->andReturn($o['avatar'] ?? 'https://example.com/avatar.jpg');

        $user->token = $o['token'] ?? 'access-token-abc';
        $user->refreshToken = $o['refreshToken'] ?? 'refresh-token-def';
        $user->expiresIn = $o['expiresIn'] ?? 3600;

        return $user;
    }

    public function test_creates_user_connected_account_and_team_from_provider(): void
    {
        $providerUser = $this->fakeProviderUser();

        $action = new CreateUserWithTeamsFromProvider(new CreateConnectedAccount);
        $user = $action->create('google', $providerUser);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->assertDatabaseHas('connected_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '1234567890',
        ]);

        $this->assertDatabaseHas('teams', [
            'user_id' => $user->id,
            'personal_team' => true,
        ]);
    }

    /**
     * The static analysis baseline flags User::setProfilePhotoFromUrl() as
     * nonexistent, and both provider-sign-up actions call it (guarded by the
     * provider-avatars feature). It is actually provided by the
     * SetsProfilePhotoFromUrl trait — this proves the call is not a fatal.
     */
    public function test_user_can_set_profile_photo_from_url(): void
    {
        Http::fake(['*' => Http::response('image-bytes', 200)]);
        Storage::fake('public');

        $user = User::factory()->create(['profile_photo_path' => null]);

        $user->setProfilePhotoFromUrl('https://example.com/avatar.jpg');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/avatar.jpg');
        $this->assertNotNull($user->fresh()->profile_photo_path);
    }

    public function test_create_connected_account_persists_provider_tokens(): void
    {
        $user = User::factory()->create();

        $providerUser = $this->fakeProviderUser([
            'token' => 'tok-1',
            'refreshToken' => 'ref-1',
            'expiresIn' => 7200,
        ]);

        $account = (new CreateConnectedAccount)->create($user, 'Google', $providerUser);

        $this->assertTrue($account->exists);
        $this->assertDatabaseHas('connected_accounts', [
            'id' => $account->id,
            'user_id' => $user->id,
            'provider' => 'google', // lower-cased by the action
            'provider_id' => '1234567890',
        ]);
        // token/refresh_token are encrypted at rest — assert via the model, which decrypts.
        $this->assertSame('tok-1', $account->fresh()->token);
        $this->assertSame('ref-1', $account->fresh()->refresh_token);
        $this->assertNotNull($account->fresh()->expires_at);
    }

    public function test_update_connected_account_persists_new_tokens(): void
    {
        $user = User::factory()->create();

        $account = (new CreateConnectedAccount)->create(
            $user,
            'google',
            $this->fakeProviderUser(['token' => 'old-token', 'refreshToken' => 'old-refresh']),
        );

        $updated = app(UpdateConnectedAccount::class)->update(
            $user,
            $account,
            'google',
            $this->fakeProviderUser(['token' => 'new-token', 'refreshToken' => 'new-refresh']),
        );

        $this->assertDatabaseHas('connected_accounts', [
            'id' => $updated->id,
            'user_id' => $user->id,
        ]);
        // token/refresh_token are encrypted at rest — assert via the model, which decrypts.
        $this->assertSame('new-token', $updated->fresh()->token);
        $this->assertSame('new-refresh', $updated->fresh()->refresh_token);
    }

    public function test_set_user_password_hashes_and_persists(): void
    {
        $user = User::factory()->create();

        (new SetUserPassword)->set($user, [
            'password' => 'S3cret-Passw0rd',
            'password_confirmation' => 'S3cret-Passw0rd',
        ]);

        $this->assertTrue(Hash::check('S3cret-Passw0rd', $user->fresh()->password));
    }

    /**
     * The SocialstreamServiceProvider must wire the app's ConnectedAccount model
     * so social sign-up gets the IsTenantModel/account_type/is_primary behaviour
     * and the app's event map — not the base package model. The package provider
     * boots first and resets the resolver to its own base model, so this only
     * holds because the app provider overrides it in boot().
     */
    public function test_socialstream_resolves_the_app_connected_account_model(): void
    {
        $this->assertSame(ConnectedAccount::class, Socialstream::connectedAccountModel());
    }

    public function test_create_connected_account_returns_app_model_and_dispatches_created_event(): void
    {
        Event::fake([ConnectedAccountCreated::class]);

        $user = User::factory()->create();

        $account = (new CreateConnectedAccount)->create($user, 'google', $this->fakeProviderUser());

        $this->assertInstanceOf(ConnectedAccount::class, $account);

        Event::assertDispatched(
            ConnectedAccountCreated::class,
            fn (ConnectedAccountCreated $event): bool => $event->connectedAccount instanceof ConnectedAccount
                && $event->connectedAccount->is($account),
        );
    }

    public function test_handle_invalid_state_rethrows(): void
    {
        $this->expectException(InvalidStateException::class);

        (new HandleInvalidState)->handle(new InvalidStateException);
    }
}
