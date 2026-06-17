<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('My Bookings')]
class MyBookings extends Component
{
    /** all | confirmed | awaiting | cancelled */
    #[Url]
    public string $filter = 'all';

    public function render()
    {
        $bookings = auth()->user()->bookings()
            ->with('court.venue')
            ->when($this->filter === 'confirmed', fn ($q) => $q->where('status', BookingStatus::Confirmed->value))
            ->when($this->filter === 'awaiting', fn ($q) => $q->where('status', BookingStatus::Pending->value)
                ->where('hold_expires_at', '>', now()))
            ->when($this->filter === 'cancelled', fn ($q) => $q->where(function ($w) {
                $w->whereIn('status', [BookingStatus::Cancelled->value, BookingStatus::Expired->value])
                    ->orWhere(fn ($p) => $p->where('status', BookingStatus::Pending->value)
                        ->where('hold_expires_at', '<=', now()));
            }))
            ->latest() // newest booking first
            ->get();

        return view('livewire.my-bookings', ['bookings' => $bookings]);
    }
}
