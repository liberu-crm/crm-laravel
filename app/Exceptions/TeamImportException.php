<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A cross-environment backup import could not proceed (unreadable archive,
 * missing manifest, unsupported format). Message is safe to show a super admin.
 */
class TeamImportException extends RuntimeException {}
