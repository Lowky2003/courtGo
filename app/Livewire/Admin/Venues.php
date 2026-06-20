<?php

namespace App\Livewire\Admin;

use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Approve Venues')]
class Venues extends Component
{
    /** Approve a venue so it becomes visible and bookable to customers. */
    public function approve(int $venueId): void
    {
        $venue = Venue::findOrFail($venueId);

        $venue->update(['approved_at' => now()]);
    }

    public function render()
    {
        return view('livewire.admin.venues', [
            'venues' => Venue::with('owner')
                ->withCount('courts')
                ->orderByRaw('approved_at IS NULL DESC') // pending venues first
                ->orderBy('name')
                ->get(),
        ]);
    }
}
