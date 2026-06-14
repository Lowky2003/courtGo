<?php

namespace App\Livewire;

use App\Models\Court;
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
        $courts = Court::query()
            ->bookable()
            ->when($this->sport, fn ($q) => $q->where('sport', 'like', '%'.$this->sport.'%'))
            ->when($this->city, fn ($q) => $q->whereHas('venue', fn ($v) => $v->where('city', 'like', '%'.$this->city.'%')))
            ->with('venue')
            ->orderBy('sport')
            ->get();

        return view('livewire.browse', ['courts' => $courts]);
    }
}
