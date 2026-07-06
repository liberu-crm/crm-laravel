<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A team restore could not proceed (bad/incomplete source, missing team,
 * unsupported format). Carries a message safe to surface to a super admin.
 */
class TeamRestoreException extends RuntimeException {}
