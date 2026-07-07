<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Enums\Role;
use App\Exceptions\PortalOnboardingException;
use App\Models\Contact;
use App\Models\User;
use App\Notifications\PortalInvitation;
use App\Services\AuditLogService;
use Filament\Facades\Filament;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisions a Contact as a portal customer: a User with the global `customer`
 * role, current_team_id = the contact's tenant (unblocks ticket creation), a
 * verified email, and a portal password-reset link mailed so they can set a
 * password and log in.
 */
class InvitePortalCustomer
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function __invoke(Contact $contact): User
    {
        $email = $contact->getAttribute('email');
        if (blank($email)) {
            throw new PortalOnboardingException('This contact has no email address to invite.');
        }

        if ($this->emailBelongsToStaff($email)) {
            throw new PortalOnboardingException('That email already belongs to a staff account.');
        }

        // Random password so the account cannot be logged into until the reset
        // link is used; firstOrCreate keeps re-invites idempotent.
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $contact->getAttribute('name') ?: $email,
                'password' => Hash::make(Str::random(40)),
            ],
        );

        $user->forceFill([
            'current_team_id' => $contact->getAttribute('team_id'),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        setPermissionsTeamId(null);
        if (! $user->hasRole(Role::Customer)) {
            $user->assignRole(Role::Customer->value);
        }

        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);
        $url = Filament::getPanel('portal')->getResetPasswordUrl($token, $user);
        $user->notify(new PortalInvitation($url));

        $this->audit->record('portal.invited', "Invited {$email} to the customer portal.", $user);

        return $user;
    }

    /**
     * True when a user with this email already holds any role other than
     * `customer` (in any team or globally) — a staff account we must never
     * downgrade. Queried team-agnostically, mirroring User::hasGlobalRole.
     */
    private function emailBelongsToStaff(string $email): bool
    {
        $user = User::where('email', $email)->first();
        if (! $user instanceof User) {
            return false;
        }

        $tables = config('permission.table_names');
        $morphKey = config('permission.column_names.model_morph_key');
        $roleKey = app(PermissionRegistrar::class)->pivotRole;

        return DB::table($tables['model_has_roles'])
            ->join($tables['roles'], $tables['roles'].'.id', '=', $tables['model_has_roles'].'.'.$roleKey)
            ->where($tables['model_has_roles'].'.model_type', $user->getMorphClass())
            ->where($tables['model_has_roles'].'.'.$morphKey, $user->getKey())
            ->where($tables['roles'].'.name', '!=', Role::Customer->value)
            ->exists();
    }
}
