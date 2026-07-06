<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * The restore target team already holds data. Restore is same-team disaster
 * recovery and refuses to run against a populated team (would duplicate rows
 * and collide on original PKs).
 */
class TeamNotEmptyException extends TeamRestoreException {}
