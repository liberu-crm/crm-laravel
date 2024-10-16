<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'triggers',
        'actions',
    ];

    protected $casts = [
        'triggers' => 'json',
        'actions' => 'json',
    ];

    public function leads()
    {
        return $this->belongsToMany(Lead::class);
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }

    public function deals()
    {
        return $this->belongsToMany(Deal::class);
    }
}