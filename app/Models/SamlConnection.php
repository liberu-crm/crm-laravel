<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A team's SAML identity-provider connection (G2 SAML). IsTenantModel gives the
 * team scope + team_id auto-stamp + team() relationship. The x509 cert is the
 * IdP's public signing certificate (not a secret, so not encrypted).
 */
class SamlConnection extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'team_id',
        'idp_entity_id',
        'idp_sso_url',
        'idp_slo_url',
        'idp_x509_cert',
        'enabled',
        'allow_jit',
        'allowed_domain',
        'require_sso',
        'role_mappings',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'allow_jit' => 'boolean',
        'require_sso' => 'boolean',
        'role_mappings' => 'array',
    ];

    /**
     * The team role an IdP group maps to (first match), or null. Only the four
     * assignable team roles are honoured — super_admin/customer are ignored.
     *
     * @param  array<int, string>  $groups
     */
    public function roleForGroups(array $groups): ?Role
    {
        $teamRoles = [
            Role::Admin->value,
            Role::Manager->value,
            Role::SalesRep->value,
            Role::Free->value,
        ];

        foreach ((array) $this->getAttribute('role_mappings') as $group => $role) {
            if (in_array($group, $groups, true) && in_array($role, $teamRoles, true)) {
                return Role::from($role);
            }
        }

        return null;
    }
}
