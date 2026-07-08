<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A team's SSO (OIDC) identity-provider connection. IsTenantModel gives the
 * team scope, team_id auto-stamp, and team() relationship. client_secret is
 * encrypted at rest.
 */
class SsoConnection extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'team_id',
        'provider',
        'client_id',
        'client_secret',
        'issuer_url',
        'enabled',
        'allow_jit',
        'allowed_domain',
        'require_sso',
        'token_auth_method',
        'role_mappings',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'enabled' => 'boolean',
        'allow_jit' => 'boolean',
        'require_sso' => 'boolean',
        'role_mappings' => 'array',
    ];

    /**
     * The team role an IdP's groups map to, or null. Only the four team roles are
     * assignable — a mapping to super_admin/customer is ignored.
     *
     * @param  array<int, string>  $groups
     */
    public function roleForGroups(array $groups): ?Role
    {
        $teamRoles = [Role::Admin->value, Role::Manager->value, Role::SalesRep->value, Role::Free->value];

        foreach ((array) $this->getAttribute('role_mappings') as $group => $role) {
            if (in_array($group, $groups, true) && in_array($role, $teamRoles, true)) {
                return Role::from($role);
            }
        }

        return null;
    }
}
