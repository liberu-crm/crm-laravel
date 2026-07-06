<?php

namespace App\Contracts;

/**
 * A model whose rows have an owner column for record-level scoping.
 * Implemented by the RestrictsToOwner trait.
 */
interface OwnsRecords
{
    public function ownerColumn(): string;
}
