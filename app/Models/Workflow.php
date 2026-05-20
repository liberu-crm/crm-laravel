<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\IsTenantModel;

class Workflow extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'description',
        'triggers',
        'actions',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'triggers' => 'json',
        'actions' => 'json',
        'is_active' => 'boolean',
        'metadata' => 'json',
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

    public function workflowTriggers()
    {
        return $this->hasMany(WorkflowTrigger::class);
    }

    public function workflowActions()
    {
        return $this->hasMany(WorkflowAction::class);
    }

    public function executions()
    {
        return $this->hasMany(WorkflowExecution::class);
    }
}
