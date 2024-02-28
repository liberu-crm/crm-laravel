<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $primaryKey = 'contact_id';

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone_number',
    ];

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}
