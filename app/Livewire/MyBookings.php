<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('My Bookings')]
class MyBookings extends Component
{
    public function render()
    {
        $bookings = auth()->user()->bookings()
            ->with('court.venue')
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        return view('livewire.my-bookings', ['bookings' => $bookings]);
    }
}
