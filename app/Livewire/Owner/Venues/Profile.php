<?php

namespace App\Livewire\Owner\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Venue Profile')]
class Profile extends Component
{
    use AuthorizesRequests;

    public Venue $venue;

    /** @var array<int, string> */
    public array $amenities = [];

    public function mount(Venue $venue): void
    {
        $this->authorize('update', $venue);

        $this->venue = $venue;
        $this->amenities = $venue->amenities ?? [];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'amenities' => 'array',
            'amenities.*' => ['string', Rule::in(array_keys(config('courtgo.amenities')))],
        ]);

        $this->venue->update(['amenities' => $validated['amenities']]);

        session()->flash('status', 'Venue profile updated.');
    }

    public function render()
    {
        return view('livewire.owner.venues.profile', [
            'allAmenities' => config('courtgo.amenities'),
            'photos' => $this->venue->photos()->get(),
        ]);
    }
}
