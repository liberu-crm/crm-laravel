<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OAuthConfiguration extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $table = 'oauth_configurations';

    protected $fillable = [
        'service_name',
        'client_id',
        'client_secret',
        'additional_settings',
    ];

    protected $casts = [
        'additional_settings' => 'array',
    ];

    public static function getConfig($serviceName)
    {
        return self::where('service_name', $serviceName)->first();
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthConfiguration extends Model
{
    protected $fillable = [
        'service_name',
        'client_id',
        'client_secret',
        'additional_settings'
    ];

    protected $casts = [
        'additional_settings' => 'array'
    ];

    public static function getConfig($service)
    {
        return static::where('service_name', $service)->first();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OAuthConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'client_id',
        'client_secret',
        'additional_settings',
        'account_name',
        'user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'webhook_url',
        'is_active'
    ];

    protected $casts = [
        'additional_settings' => 'array',
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public static function getConfig($service)
    {
        return static::where('service_name', $service)
                    ->where('is_active', true)
                    ->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}