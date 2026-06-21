<?php

namespace App\Livewire\Owner;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Bookings')]
class Bookings extends Component
{
    use WithPagination;

    /** upcoming | past | all */
    #[Url]
    public string $filter = 'upcoming';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $today = now()->toDateString();

        $bookings = Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->whereHas('court.venue', fn ($v) => $v->where('owner_id', auth()->id()))
            ->with(['court.venue', 'customer'])
            ->when($this->filter === 'upcoming', fn ($q) => $q->whereDate('booking_date', '>=', $today)
                ->orderBy('booking_date')->orderBy('start_time'))
            ->when($this->filter === 'past', fn ($q) => $q->whereDate('booking_date', '<', $today)
                ->orderByDesc('booking_date')->orderByDesc('start_time'))
            ->when($this->filter === 'all', fn ($q) => $q->orderByDesc('booking_date')->orderByDesc('start_time'))
            ->paginate(20);

        return view('livewire.owner.bookings', ['bookings' => $bookings]);
    }
}
