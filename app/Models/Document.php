<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'version',
        'documentable_id',
        'documentable_type',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}