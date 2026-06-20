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
        $bookable = $this->court->isBookable();

        // Don't surface bookable slots for a court that isn't live (e.g. its venue
        // is still pending admin approval) — the reserve path rejects them anyway,
        // so showing clickable "Book & pay" buttons would only mislead the customer.
        $sessions = $bookable
            ? app(AvailabilityService::class)->availableSessions($this->court, Carbon::parse($this->date))
            : collect();

        return view('livewire.court-show', ['sessions' => $sessions, 'bookable' => $bookable]);
    }
}
