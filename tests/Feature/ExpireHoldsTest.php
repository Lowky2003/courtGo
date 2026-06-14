<?php

use App\Enums\BookingStatus;
use App\Models\Booking;

test('it expires pending holds whose hold time has passed', function () {
    $booking = Booking::factory()->pending()->create([
        'hold_expires_at' => now()->addMinutes(10),
    ]);

    $this->travelTo(now()->addMinutes(11)); // the hold is now stale

    $this->artisan('bookings:expire-holds')->assertSuccessful();

    expect($booking->fresh()->status)->toBe(BookingStatus::Expired);

    $this->travelBack();
});

test('it keeps holds that have not yet expired', function () {
    $booking = Booking::factory()->pending()->create([
        'hold_expires_at' => now()->addMinutes(10),
    ]);

    $this->travelTo(now()->addMinutes(5));

    $this->artisan('bookings:expire-holds')->assertSuccessful();

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);

    $this->travelBack();
});

test('it does not touch confirmed bookings', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatus::Confirmed,
        'hold_expires_at' => now()->subDay(),
    ]);

    $this->artisan('bookings:expire-holds')->assertSuccessful();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});
