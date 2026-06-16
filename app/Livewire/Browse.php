<?php

namespace App\Livewire;

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
    public string $sport = '';

    #[Url]
    public string $city = '';

    public function render()
    {
        $venues = Venue::query()
            ->bookable()
            ->when($this->city, fn ($q) => $q->where('city', 'like', '%'.$this->city.'%'))
            ->when($this->sport, fn ($q) => $q->whereHas(
                'courts',
                fn ($c) => $c->bookable()->where('sport', 'like', '%'.$this->sport.'%')
            ))
            ->with(['courts' => fn ($q) => $q->bookable()])
            ->orderBy('name')
            ->get();

        return view('livewire.browse', ['venues' => $venues]);
    }
}
