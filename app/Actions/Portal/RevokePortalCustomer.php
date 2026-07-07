<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Enums\Role;
use App\Exceptions\PortalOnboardingException;
use App\Models\Contact;
use App\Support\PortalCustomer;

/**
 * Removes a customer's portal access by stripping the global `customer` role.
 * canAccessPanel('portal') keys on that role, so access is denied on the next
 * request. The User row, its team, and its tickets/documents are preserved;
 * re-inviting restores access.
 */
class RevokePortalCustomer
{
    public function __invoke(Contact $contact): void
    {
        $user = PortalCustomer::forEmail($contact->getAttribute('email'));
        if ($user === null) {
            throw new PortalOnboardingException('This contact is not a portal customer.');
        }

        setPermissionsTeamId(null);
        $user->removeRole(Role::Customer->value);
    }
}
