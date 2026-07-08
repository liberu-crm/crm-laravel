<?php

declare(strict_types=1);

namespace App\Models;

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
        'idp_x509_cert',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
