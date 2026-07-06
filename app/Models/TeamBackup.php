<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracking row for a team data-export. Not IsTenantModel — backups are an
 * ops/super-admin concern, managed outside team tenancy.
 */
class TeamBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'disk',
        'path',
        'size_bytes',
        'status',
        'error',
        'created_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
