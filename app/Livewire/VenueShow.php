<?php

namespace App\Livewire;

use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Choose a Court')]
class VenueShow extends Component
{
    public Venue $venue;

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
    }

    public function render()
    {
        return view('livewire.venue-show', [
            'courts' => $this->venue->courts()->bookable()->orderBy('name')->get(),
        ]);
    }
}
