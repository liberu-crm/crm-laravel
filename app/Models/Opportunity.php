<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $primaryKey = 'opportunity_id';

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
