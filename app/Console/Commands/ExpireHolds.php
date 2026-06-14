<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bookings:expire-holds')]
#[Description('Expire pending bookings whose payment hold has passed, freeing the slot')]
class ExpireHolds extends Command
{
    public function handle(): int
    {
        $count = Booking::query()
            ->where('status', BookingStatus::Pending->value)
            ->where('hold_expires_at', '<=', now())
            ->update(['status' => BookingStatus::Expired->value]);

        $this->info("Expired {$count} stale hold(s).");

        return self::SUCCESS;
    }
}
