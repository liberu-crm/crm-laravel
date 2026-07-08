<?php

declare(strict_types=1);

namespace App\Models;

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
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
        'enabled' => 'boolean',
        'allow_jit' => 'boolean',
        'require_sso' => 'boolean',
    ];
}
