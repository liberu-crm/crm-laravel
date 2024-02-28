<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $primaryKey = 'note_id';

    protected $fillable = [
        'content',
        'contact_id',
        'company_id',
        'opportunity_id',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
}
