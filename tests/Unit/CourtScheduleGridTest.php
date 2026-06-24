<?php

use App\Models\Booking;
use App\Models\Court;
use App\Support\CourtScheduleGrid;

/** A Court with a fixed id (no DB needed for the pure grid maths). */
function scheduleCourt(int $id, string $name): Court
{
    $court = new Court(['name' => $name]);
    $court->id = $id;

    return $court;
}

function scheduleBooking(int $courtId, string $start, string $end): Booking
{
    return new Booking(['court_id' => $courtId, 'start_time' => $start, 'end_time' => $end]);
}

/** The hours (0–23) at which a given court is booked, across all rows. */
function bookedHoursFor(array $grid, int $courtId): array
{
    return collect($grid['rows'])
        ->filter(fn ($row) => in_array($courtId, $row['bookedCourtIds'], true))
        ->pluck('hour')
        ->all();
}

it('marks a court booked for exactly the hour of a one-hour booking', function () {
    $courts = collect([scheduleCourt(1, 'Court A'), scheduleCourt(2, 'Court B')]);
    $bookings = collect([scheduleBooking(1, '19:00', '20:00')]);

    $grid = (new CourtScheduleGrid)->build($courts, $bookings);

    expect($grid['hasBookings'])->toBeTrue()
        ->and(bookedHoursFor($grid, 1))->toBe([19])
        ->and(bookedHoursFor($grid, 2))->toBe([]);
});

it('marks every hour a multi-hour booking overlaps', function () {
    $bookings = collect([scheduleBooking(1, '18:00', '21:00')]);

    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), $bookings);

    expect(bookedHoursFor($grid, 1))->toBe([18, 19, 20]);
});

it('marks only the overlapped hour for a half-hour booking', function () {
    $bookings = collect([scheduleBooking(1, '19:00', '19:30')]);

    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), $bookings);

    expect(bookedHoursFor($grid, 1))->toBe([19]);
});

it('treats a midnight end as end-of-day and extends the grid to 23:00', function () {
    $bookings = collect([scheduleBooking(1, '22:00', '00:00')]);

    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), $bookings);

    expect(bookedHoursFor($grid, 1))->toBe([22, 23])
        ->and(collect($grid['rows'])->last()['hour'])->toBe(23);
});

it('marks multiple courts booked in the same hour', function () {
    $courts = collect([scheduleCourt(1, 'Court A'), scheduleCourt(2, 'Court B'), scheduleCourt(3, 'Court C')]);
    $bookings = collect([
        scheduleBooking(1, '20:00', '21:00'),
        scheduleBooking(3, '20:00', '21:00'),
    ]);

    $grid = (new CourtScheduleGrid)->build($courts, $bookings);

    $row = collect($grid['rows'])->firstWhere('hour', 20);

    expect($row['bookedCourtIds'])->toEqualCanonicalizing([1, 3]);
});

it('groups the bookings under their court for each hour', function () {
    $bookings = collect([scheduleBooking(1, '20:00', '21:00')]);

    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), $bookings);

    $row = collect($grid['rows'])->firstWhere('hour', 20);

    expect($row['byCourt'])->toHaveKey(1)
        ->and($row['byCourt'][1])->toHaveCount(1);
});

it('widens the grid to the venue opening hours, with unbooked hours free', function () {
    $bookings = collect([scheduleBooking(1, '19:00', '20:00')]);
    $hours = ['open' => '17:00', 'close' => '22:00'];

    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), $bookings, $hours);

    expect(collect($grid['rows'])->pluck('hour')->all())->toBe([17, 18, 19, 20, 21])
        ->and(bookedHoursFor($grid, 1))->toBe([19]);
});

it('never clips a booking that falls outside opening hours', function () {
    $bookings = collect([scheduleBooking(1, '06:00', '07:00')]);
    $hours = ['open' => '17:00', 'close' => '22:00'];

    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), $bookings, $hours);

    expect(collect($grid['rows'])->first()['hour'])->toBe(6)
        ->and(bookedHoursFor($grid, 1))->toBe([6]);
});

it('ignores opening hours flagged closed', function () {
    $bookings = collect([scheduleBooking(1, '19:00', '20:00')]);
    $hours = ['closed' => true, 'open' => '17:00', 'close' => '22:00'];

    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), $bookings, $hours);

    expect(collect($grid['rows'])->pluck('hour')->all())->toBe([19]);
});

it('falls back to an 8am-10pm window with no bookings and no hours', function () {
    $grid = (new CourtScheduleGrid)->build(collect([scheduleCourt(1, 'Court A')]), collect());

    expect($grid['hasBookings'])->toBeFalse()
        ->and(collect($grid['rows'])->pluck('hour')->all())->toBe(range(8, 21));
});

it('lists the bookings overlapping a given hour', function () {
    $bookings = collect([
        scheduleBooking(1, '19:00', '20:00'),
        scheduleBooking(2, '19:30', '21:00'),
        scheduleBooking(3, '21:00', '22:00'), // starts after the 7pm hour
    ]);

    $atSeven = (new CourtScheduleGrid)->bookingsForHour($bookings, 19);

    expect($atSeven->pluck('court_id')->all())->toEqualCanonicalizing([1, 2]);
});
