<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormBuilder extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'description',
        'fields',
        'validation_rules',
        'conditional_logic',
        'steps',
        'is_multi_step',
        'team_id',
    ];

    protected $casts = [
        'fields'           => 'array',
        'validation_rules' => 'array',
        'conditional_logic' => 'array',
        'steps'            => 'array',
        'is_multi_step'    => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Return the fields for a specific step (1-based index).
     * Falls back to all fields when the form is not multi-step.
     *
     * @param  int $step  Step number (1-based).
     * @return array
     */
    public function getFieldsForStep(int $step): array
    {
        if (!$this->is_multi_step || empty($this->steps)) {
            return $this->fields ?? [];
        }

        $stepConfig = collect($this->steps)->firstWhere('step', $step);

        if (!$stepConfig || empty($stepConfig['field_keys'])) {
            return [];
        }

        $fieldKeys = $stepConfig['field_keys'];

        return array_values(
            array_filter(
                $this->fields ?? [],
                fn ($field) => in_array($field['key'] ?? null, $fieldKeys, true)
            )
        );
    }

    /**
     * Evaluate conditional logic rules to determine which fields should be
     * visible given the current set of form values.
     *
     * Each rule in `conditional_logic` has the shape:
     * {
     *   "field_key": "field_to_show_or_hide",
     *   "action": "show" | "hide",
     *   "conditions": [
     *     {"field": "trigger_field", "operator": "==" | "!=" | ">" | "<", "value": "..."}
     *   ],
     *   "logic": "AND" | "OR"   (default "AND")
     * }
     *
     * @param  array $values  Current field values keyed by field key.
     * @return array<string, bool>  Map of field_key => visible.
     */
    public function evaluateConditionalLogic(array $values): array
    {
        // Start with every field visible
        $visibility = [];
        foreach ($this->fields ?? [] as $field) {
            $key = $field['key'] ?? null;
            if ($key) {
                $visibility[$key] = true;
            }
        }

        foreach ($this->conditional_logic ?? [] as $rule) {
            $fieldKey   = $rule['field_key'] ?? null;
            $action     = $rule['action'] ?? 'show';
            $conditions = $rule['conditions'] ?? [];
            $logic      = strtoupper($rule['logic'] ?? 'AND');

            if (!$fieldKey || empty($conditions)) {
                continue;
            }

            $results = array_map(
                fn ($cond) => $this->evaluateCondition($cond, $values),
                $conditions
            );

            $passes = $logic === 'OR'
                ? in_array(true, $results, true)
                : !in_array(false, $results, true);

            if ($action === 'show') {
                $visibility[$fieldKey] = $passes;
            } elseif ($action === 'hide') {
                $visibility[$fieldKey] = !$passes;
            }
        }

        return $visibility;
    }

    /**
     * Evaluate a single condition against the provided field values.
     */
    private function evaluateCondition(array $condition, array $values): bool
    {
        $field    = $condition['field']    ?? null;
        $operator = $condition['operator'] ?? '==';
        $expected = $condition['value']    ?? null;
        $actual   = $values[$field]        ?? null;

        return match ($operator) {
            '=='  => $actual == $expected,
            '!='  => $actual != $expected,
            '>'   => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            '<'   => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            '>='  => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            '<='  => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            default    => false,
        };
    }
}
