<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $ip_address
 * @property string $created_at
 * @property string $updated_at
 */
class Activation extends Model
{

    use IsTenantModel;
    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'token', 'ip_address', 'created_at', 'updated_at'];
}
