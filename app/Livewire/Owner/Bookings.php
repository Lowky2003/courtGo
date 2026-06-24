<?php

namespace App\Livewire\Owner;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Venue;
use App\Support\CourtScheduleGrid;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bookings')]
class Bookings extends Component
{
    /** The day being viewed, as Y-m-d. */
    #[Url]
    public string $date = '';

    /** Whose courts to show (one venue at a time). */
    #[Url]
    public ?int $venueId = null;

    /** The hour row (0–23) the owner has selected, or null. */
    public ?int $selectedHour = null;

    public function mount(): void
    {
        if (! $this->isValidDate($this->date)) {
            $this->date = now()->toDateString();
        }

        // Default to (or fall back to) the owner's first venue.
        if (! $this->ownsVenue($this->venueId)) {
            $this->venueId = auth()->user()->venues()->orderBy('id')->value('id');
        }

        $this->defaultSelectedHour();
    }

    public function updatedDate(): void
    {
        if (! $this->isValidDate($this->date)) {
            $this->date = now()->toDateString();
        }

        $this->defaultSelectedHour();
    }

    public function updatedVenueId(): void
    {
        if (! $this->ownsVenue($this->venueId)) {
            $this->venueId = auth()->user()->venues()->orderBy('id')->value('id');
        }

        $this->defaultSelectedHour();
    }

    /** Step the calendar day backwards/forwards. */
    public function changeDay(int $deltaDays): void
    {
        $this->date = Carbon::parse($this->date)->addDays($deltaDays)->toDateString();
        $this->defaultSelectedHour();
    }

    public function goToday(): void
    {
        $this->date = now()->toDateString();
        $this->defaultSelectedHour();
    }

    public function selectHour(int $hour): void
    {
        $this->selectedHour = $hour;
    }

    /** Venues the owner can switch between. */
    #[Computed]
    public function venues()
    {
        return auth()->user()->venues()->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        /** @var Venue|null $venue */
        $venue = $this->venueId ? auth()->user()->venues()->find($this->venueId) : null;

        if (! $venue) {
            return view('livewire.owner.bookings', ['grid' => null, 'selectedBookings' => collect()]);
        }

        $date = Carbon::parse($this->date);
        $bookings = $this->bookingsFor($venue, $date);

        // Columns: the venue's active courts, plus any court booked that day (so a
        // court deactivated after a booking still shows up).
        $bookedCourtIds = $bookings->pluck('court_id')->unique();
        $courts = $venue->courts()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $bookedCourtIds))
            ->orderBy('name')
            ->get(['id', 'name', 'sport']);

        $hours = ($venue->opening_hours ?? [])[$date->dayOfWeek] ?? null;

        $service = new CourtScheduleGrid;
        $grid = $service->build($courts, $bookings, $hours) + ['venue' => $venue, 'date' => $date];

        $selectedBookings = $this->selectedHour !== null
            ? $service->bookingsForHour($bookings, $this->selectedHour)
            : collect();

        return view('livewire.owner.bookings', [
            'grid' => $grid,
            'selectedBookings' => $selectedBookings,
        ]);
    }

    /** Confirmed bookings on the given day across the venue's courts. */
    private function bookingsFor(Venue $venue, Carbon $date)
    {
        return Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->whereDate('booking_date', $date->toDateString())
            ->whereHas('court', fn ($q) => $q->where('venue_id', $venue->id))
            ->with(['court:id,name', 'customer:id,name'])
            ->orderBy('start_time')
            ->get(['id', 'court_id', 'customer_id', 'start_time', 'end_time']);
    }

    private function ownsVenue(?int $venueId): bool
    {
        return $venueId !== null && auth()->user()->venues()->whereKey($venueId)->exists();
    }

    /** When viewing today, pre-select the current hour; otherwise clear the selection. */
    private function defaultSelectedHour(): void
    {
        $this->selectedHour = $this->date === now()->toDateString()
            ? (int) now()->format('G')
            : null;
    }

    private function isValidDate(string $date): bool
    {
        return $date !== '' && Carbon::hasFormat($date, 'Y-m-d');
    }
}
