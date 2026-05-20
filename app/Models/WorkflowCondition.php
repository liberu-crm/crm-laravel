<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowCondition extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'workflow_action_id',
        'field',
        'operator',
        'value',
        'logical_operator',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    const OPERATOR_EQUALS = 'equals';
    const OPERATOR_NOT_EQUALS = 'not_equals';
    const OPERATOR_CONTAINS = 'contains';
    const OPERATOR_NOT_CONTAINS = 'not_contains';
    const OPERATOR_GREATER_THAN = 'greater_than';
    const OPERATOR_LESS_THAN = 'less_than';
    const OPERATOR_IS_SET = 'is_set';
    const OPERATOR_IS_NOT_SET = 'is_not_set';
    const OPERATOR_IN_LIST = 'in_list';
    const OPERATOR_NOT_IN_LIST = 'not_in_list';

    const LOGICAL_AND = 'and';
    const LOGICAL_OR = 'or';

    public function workflowAction(): BelongsTo
    {
        return $this->belongsTo(WorkflowAction::class);
    }

    /**
     * Evaluate if the condition is met
     */
    public function evaluate($entity): bool
    {
        $fieldValue = data_get($entity, $this->field);
        $compareValue = $this->value;

        return match ($this->operator) {
            self::OPERATOR_EQUALS => $fieldValue == $compareValue,
            self::OPERATOR_NOT_EQUALS => $fieldValue != $compareValue,
            self::OPERATOR_CONTAINS => str_contains($fieldValue, $compareValue),
            self::OPERATOR_NOT_CONTAINS => !str_contains($fieldValue, $compareValue),
            self::OPERATOR_GREATER_THAN => $fieldValue > $compareValue,
            self::OPERATOR_LESS_THAN => $fieldValue < $compareValue,
            self::OPERATOR_IS_SET => !empty($fieldValue),
            self::OPERATOR_IS_NOT_SET => empty($fieldValue),
            self::OPERATOR_IN_LIST => in_array($fieldValue, (array)$compareValue),
            self::OPERATOR_NOT_IN_LIST => !in_array($fieldValue, (array)$compareValue),
            default => false,
        };
    }
}
