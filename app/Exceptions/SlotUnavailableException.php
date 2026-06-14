<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a court session can't be reserved (already taken, court not live,
 * or the date doesn't match the session's weekday).
 */
class SlotUnavailableException extends RuntimeException
{
}
