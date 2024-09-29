<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $primaryKey = 'task_id';

    protected $fillable = [
        'name',
        'description',
        'due_date',
        'status',
        'contact_id',
        'company_id',
        'opportunity_id',
        'reminder_date',
        'reminder_sent',
    ];

    protected $casts = [
        'reminder_date' => 'datetime',
        'reminder_sent' => 'boolean',
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
