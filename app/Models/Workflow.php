<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** @return HasMany<WorkflowTrigger, $this> */
    public function workflowTriggers(): HasMany
    {
        return $this->hasMany(WorkflowTrigger::class);
    }

    /** @return HasMany<WorkflowAction, $this> */
    public function workflowActions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    public function executions()
    {
        return $this->hasMany(WorkflowExecution::class);
    }
}
