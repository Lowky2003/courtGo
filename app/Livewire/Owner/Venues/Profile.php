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

    /** Autosave when a checkbox is toggled, so no ticks are lost if a photo form reloads the page. */
    public function updatedAmenities(): void
    {
        $this->save();
    }

    public function save(): void
    {
        $this->authorize('update', $this->venue);

        $validated = $this->validate([
            'amenities' => 'array',
            'amenities.*' => ['string', Rule::in(array_keys(config('courtgo.amenities')))],
        ]);

        $this->venue->update(['amenities' => $validated['amenities']]);

        session()->flash('status', 'Amenities saved.');
    }

    public function render()
    {
        return view('livewire.owner.venues.profile', [
            'allAmenities' => config('courtgo.amenities'),
            'photos' => $this->venue->photos()->get(),
        ]);
    }
}
