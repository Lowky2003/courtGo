<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('a booking belongs to a customer and a court', function () {
    $booking = Booking::factory()->create();

    expect($booking->customer)->toBeInstanceOf(User::class)
        ->and($booking->court)->toBeInstanceOf(Court::class);
});

test('status is cast to the BookingStatus enum', function () {
    $booking = Booking::factory()->create(['status' => 'confirmed']);

    expect($booking->status)->toBe(BookingStatus::Confirmed);
});

test('two active bookings cannot share the same court, date and start time', function () {
    $court = Court::factory()->create();
    Booking::factory()->for($court)->create([
        'booking_date' => '2026-07-10',
        'start_time' => '09:00',
        'status' => BookingStatus::Confirmed,
    ]);

    expect(fn () => Booking::factory()->for($court)->create([
        'booking_date' => '2026-07-10',
        'start_time' => '09:00',
        'status' => BookingStatus::Pending,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('a cancelled booking frees the slot for re-booking', function () {
    $court = Court::factory()->create();
    Booking::factory()->for($court)->create([
        'booking_date' => '2026-07-10',
        'start_time' => '09:00',
        'status' => BookingStatus::Cancelled,
    ]);

    $new = Booking::factory()->for($court)->create([
        'booking_date' => '2026-07-10',
        'start_time' => '09:00',
        'status' => BookingStatus::Confirmed,
    ]);

    expect($new->exists)->toBeTrue();
});
