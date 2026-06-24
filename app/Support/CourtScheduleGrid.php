<?php

namespace App\Support;

use App\Concerns\HandlesScheduleTimes;
use Illuminate\Support\Collection;

/**
 * Builds the owner's booking calendar for a single venue and day: hour by hour,
 * which courts are booked and which are free. Lets the owner see at a glance
 * what's in use at any given time (and right now).
 *
 * Pure and Livewire-free so the overlap maths can be unit-tested directly.
 */
class CourtScheduleGrid
{
    use HandlesScheduleTimes;

    /**
     * @param  Collection<int, \App\Models\Court>  $courts  the grid columns
     * @param  Collection<int, \App\Models\Booking>  $bookings  confirmed bookings on the day
     * @param  array{open?: string, close?: string, closed?: bool}|null  $hours  venue opening hours for that weekday
     * @return array{
     *   courts: Collection<int, \App\Models\Court>,
     *   rows: array<int, array{hour: int, label: string, bookedCourtIds: array<int, int>, byCourt: Collection<int, Collection<int, \App\Models\Booking>>}>,
     *   hasBookings: bool,
     * }
     */
    public function build(Collection $courts, Collection $bookings, ?array $hours = null): array
    {
        $spans = $bookings->map(fn ($b) => [
            'start' => $this->slotMinutes((string) $b->start_time),
            'end' => $this->slotMinutes((string) $b->end_time, isEnd: true),
        ]);

        [$fromHour, $toHour] = $this->hourRange($spans, $hours);

        $rows = [];
        for ($hour = $fromHour; $hour < $toHour; $hour++) {
            // Bookings overlapping [H:00, H+1:00), grouped by the court they're on.
            $byCourt = $this->bookingsForHour($bookings, $hour)->groupBy('court_id');

            $rows[] = [
                'hour' => $hour,
                'label' => $this->hourLabel($hour),
                'bookedCourtIds' => $byCourt->keys()->map(fn ($id) => (int) $id)->all(),
                'byCourt' => $byCourt,
            ];
        }

        return [
            'courts' => $courts,
            'rows' => $rows,
            'hasBookings' => $bookings->isNotEmpty(),
        ];
    }

    /**
     * The confirmed bookings that overlap a given hour [H:00, H+1:00).
     *
     * @param  Collection<int, \App\Models\Booking>  $bookings
     * @return Collection<int, \App\Models\Booking>
     */
    public function bookingsForHour(Collection $bookings, int $hour): Collection
    {
        $start = $hour * 60;
        $end = $start + 60;

        return $bookings
            ->filter(fn ($b) => $this->slotMinutes((string) $b->start_time) < $end
                && $this->slotMinutes((string) $b->end_time, isEnd: true) > $start)
            ->values();
    }

    /**
     * The half-open [fromHour, toHour) the grid should span: the venue's opening
     * hours for the day (widened to whole hours) unioned with the booking span,
     * so a booking outside opening hours is never clipped. Falls back to the
     * booking span alone, or a plain 8am–10pm working window when there's neither.
     *
     * @param  Collection<int, array{start: int, end: int}>  $spans
     * @param  array{open?: string, close?: string, closed?: bool}|null  $hours
     * @return array{0: int, 1: int}
     */
    private function hourRange(Collection $spans, ?array $hours): array
    {
        $starts = [];
        $ends = [];

        if ($hours && empty($hours['closed']) && ! empty($hours['open']) && ! empty($hours['close'])) {
            $starts[] = $this->slotMinutes($hours['open']);
            $ends[] = $this->slotMinutes($hours['close'], isEnd: true);
        }

        if ($spans->isNotEmpty()) {
            $starts[] = $spans->min('start');
            $ends[] = $spans->max('end');
        }

        if (empty($starts)) {
            return [8, 22]; // sensible default when nothing constrains the window
        }

        $fromHour = intdiv(min($starts), 60);                // floor to the hour
        $toHour = min((int) ceil(max($ends) / 60), 24);      // ceil to the hour, capped at end of day

        return [$fromHour, max($toHour, $fromHour + 1)];     // always at least one row
    }

    /** A 12-hour label for an hour 0–23: "7 PM", "12 PM" (noon), "12 AM" (midnight). */
    private function hourLabel(int $hour): string
    {
        $hour12 = $hour % 12 === 0 ? 12 : $hour % 12;

        return $hour12.' '.($hour < 12 ? 'AM' : 'PM');
    }
}
