<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;

function avail(): AvailabilityService
{
    return app(AvailabilityService::class);
}

function courtWithMorningSession(Carbon $date): Court
{
    $court = Court::factory()->create();
    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);

    return $court;
}

test('a confirmed booking removes that slot from availability', function () {
    $date = Carbon::parse('2026-07-06');
    $court = courtWithMorningSession($date);

    Booking::factory()->for($court)->create([
        'booking_date' => $date->toDateString(),
        'start_time' => '09:00',
        'status' => BookingStatus::Confirmed,
    ]);

    expect(avail()->availableSessions($court, $date))->toHaveCount(0);
});

test('an unexpired pending hold removes that slot from availability', function () {
    $date = Carbon::parse('2026-07-06');
    $court = courtWithMorningSession($date);

    Booking::factory()->for($court)->create([
        'booking_date' => $date->toDateString(),
        'start_time' => '09:00',
        'status' => BookingStatus::Pending,
        'hold_expires_at' => now()->addMinutes(5),
    ]);

    expect(avail()->availableSessions($court, $date))->toHaveCount(0);
});

test('an expired pending hold does not block the slot', function () {
    $date = Carbon::parse('2026-07-06');
    $court = courtWithMorningSession($date);

    Booking::factory()->for($court)->create([
        'booking_date' => $date->toDateString(),
        'start_time' => '09:00',
        'status' => BookingStatus::Pending,
        'hold_expires_at' => now()->subMinute(),
    ]);

    expect(avail()->availableSessions($court, $date))->toHaveCount(1);
});

test('a cancelled booking does not block the slot', function () {
    $date = Carbon::parse('2026-07-06');
    $court = courtWithMorningSession($date);

    Booking::factory()->for($court)->create([
        'booking_date' => $date->toDateString(),
        'start_time' => '09:00',
        'status' => BookingStatus::Cancelled,
    ]);

    expect(avail()->availableSessions($court, $date))->toHaveCount(1);
});
