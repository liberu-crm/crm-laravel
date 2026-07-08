<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\AccessContext;

/**
 * Field-level masking (G3 ABAC). Masks configured sensitive fields in the
 * model's serialized output (toArray / toJson / API responses) for masked-role
 * viewers — WITHOUT mutating the stored attributes, so `$model->field` and any
 * save/business logic still see the real value.
 *
 * Declare fields with `protected $maskedFields = ['email', 'phone_number'];`.
 */
trait MasksFields
{
    private const MASK = '[hidden]';

    /**
     * @return array<int, string>
     */
    public function maskedFields(): array
    {
        return property_exists($this, 'maskedFields') ? $this->maskedFields : [];
    }

    /**
     * The value to display for a field: the mask when the field is masked and the
     * viewer is a masked role, otherwise the real value. Reused by attribute
     * serialization and by display surfaces (e.g. Filament columns).
     */
    public function maskFor(string $field, mixed $value): mixed
    {
        if ($value !== null
            && in_array($field, $this->maskedFields(), true)
            && AccessContext::shouldMaskFields()) {
            return self::MASK;
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->maskedFields() as $field) {
            if (array_key_exists($field, $attributes)) {
                $attributes[$field] = $this->maskFor($field, $attributes[$field]);
            }
        }

        return $attributes;
    }
}
