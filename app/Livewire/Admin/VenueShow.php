<?php

namespace App\Livewire\Admin;

use App\Models\SessionTemplate;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Venue details')]
class VenueShow extends Component
{
    public Venue $venue;

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
    }

    /** Approve this venue so it becomes visible and bookable to customers. */
    public function approve(): void
    {
        $this->venue->update(['approved_at' => now()]);
    }

    public function render()
    {
        $this->venue->load(['owner', 'courts' => fn ($q) => $q->orderBy('name'), 'photos']);

        // Price range across all active slots (not gated on bookable, so admins
        // can review pricing even before the venue is approved).
        $prices = SessionTemplate::query()
            ->where('is_active', true)
            ->whereHas('court', fn ($q) => $q->where('venue_id', $this->venue->id))
            ->pluck('price');

        return view('livewire.admin.venue-show', [
            'subscription' => $this->venue->owner->subscription($this->venue->subscriptionType()),
            'priceRange' => $prices->isEmpty() ? null : ['min' => (float) $prices->min(), 'max' => (float) $prices->max()],
        ]);
    }
}
