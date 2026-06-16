<?php

namespace App\Livewire;

use App\Models\Court;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Find a Court')]
class Browse extends Component
{
    #[Url]
    public string $name = '';

    #[Url]
    public string $sport = '';

    #[Url]
    public string $city = '';

    public function render()
    {
        // Distinct sports across bookable courts — used to populate the dropdown.
        $sports = Court::query()->bookable()->orderBy('sport')->pluck('sport')->unique()->values();

        $venues = Venue::query()
            ->bookable()
            ->when($this->name, fn ($q) => $q->where('name', 'like', '%'.$this->name.'%'))
            ->when($this->city, fn ($q) => $q->where('city', 'like', '%'.$this->city.'%'))
            ->when($this->sport, fn ($q) => $q->whereHas(
                'courts',
                fn ($c) => $c->bookable()->where('sport', $this->sport)
            ))
            ->with(['courts' => fn ($q) => $q->bookable()])
            ->orderBy('name')
            ->get();

        return view('livewire.browse', ['venues' => $venues, 'sports' => $sports]);
    }
}
