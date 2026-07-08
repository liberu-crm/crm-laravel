<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use App\Traits\MasksFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory;
    use IsTenantModel;
    use MasksFields;

    /** Sensitive fields masked in serialized output for masked-role viewers. */
    protected $maskedFields = ['deal_size'];

    protected $primaryKey = 'opportunity_id';

    public $incrementing = false;

    protected $fillable = [
        'deal_size',
        'stage',
        'closing_date',
    ];

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}
