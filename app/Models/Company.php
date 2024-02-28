<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $primaryKey = 'company_id';

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip',
        'phone_number',
        'website',
        'industry',
        'description',
    ];
    
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }
}
