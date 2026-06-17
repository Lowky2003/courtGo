<?php

namespace App\Livewire;

use App\Models\Court;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('Book a Court')]
class CourtShow extends Component
{
    public Court $court;

    public string $date = '';

    public function mount(Court $court): void
    {
        $this->court = $court;
        $this->date = Carbon::tomorrow()->toDateString();
    }

    public function render()
    {
        $sessions = app(AvailabilityService::class)
            ->availableSessions($this->court, Carbon::parse($this->date));

        return view('livewire.court-show', ['sessions' => $sessions]);
    }
}
