<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';     // slot held while the customer pays
    case Confirmed = 'confirmed'; // paid
    case Cancelled = 'cancelled'; // released
    case Expired = 'expired';     // hold timed out before payment

    /** Statuses that occupy a slot (block others from booking it). */
    public static function active(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }
}
