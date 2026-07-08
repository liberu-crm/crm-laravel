<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use JoelButcher\Socialstream\ConnectedAccount as SocialstreamConnectedAccount;
use JoelButcher\Socialstream\Events\ConnectedAccountCreated;
use JoelButcher\Socialstream\Events\ConnectedAccountDeleted;
use JoelButcher\Socialstream\Events\ConnectedAccountUpdated;

class ConnectedAccount extends SocialstreamConnectedAccount
{
    use HasFactory;
    use HasTimestamps;
    use IsTenantModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider',
        'provider_id',
        'name',
        'nickname',
        'email',
        'avatar_path',
        'token',
        'secret',
        'refresh_token',
        'expires_at',
        'account_type',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_primary' => 'boolean',
        'token' => 'encrypted',
        'secret' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => ConnectedAccountCreated::class,
        'updated' => ConnectedAccountUpdated::class,
        'deleted' => ConnectedAccountDeleted::class,
    ];

    /**
     * Decrypt the stored token before the inherited HasOAuth2Tokens::token()
     * accessor runs its refresh logic.
     *
     * A get-mutator wins over the 'encrypted' cast on read (Laravel's
     * transformModelValue calls the mutator and returns before the cast
     * branch), so the parent accessor would otherwise hand callers the raw
     * ciphertext. Writes still encrypt via the cast — this accessor defines
     * no setter, so setAttribute falls through to the encrypted cast.
     */
    protected function token(): Attribute
    {
        $accessor = parent::token();

        return Attribute::make(
            get: fn ($value, array $attributes) => ($accessor->get)(
                $value === null ? null : Crypt::decryptString($value),
                $attributes,
            ),
        );
    }

    /**
     * Get the user that owns the connected account.
     */
    public function users()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include accounts of a given type.
     *
     * @param  Builder  $query
     * @param  string  $type
     * @return Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope a query to only include primary accounts.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
