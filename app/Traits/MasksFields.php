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
     * @return array<string, mixed>
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        if (AccessContext::shouldMaskFields()) {
            foreach ($this->maskedFields() as $field) {
                if (array_key_exists($field, $attributes) && $attributes[$field] !== null) {
                    $attributes[$field] = self::MASK;
                }
            }
        }

        return $attributes;
    }
}
