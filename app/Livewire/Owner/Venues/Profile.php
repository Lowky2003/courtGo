<?php

namespace App\Livewire\Owner\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    /** Per-weekday hours: [dow => ['closed' => bool, 'open' => 'HH:MM', 'close' => 'HH:MM']]. */
    public array $openingHours = [];

    public string $pricingNote = '';

    public string $announcement = '';
    public bool $announcementActive = false;
    public ?string $announcementUntil = null;

    public string $policy = '';

    public string $contactPhone = '';
    public string $contactWhatsapp = '';
    public string $contactEmail = '';
    public string $contactWebsite = '';
    public string $contactInstagram = '';
    public string $contactFacebook = '';

    public function mount(Venue $venue): void
    {
        $this->authorize('update', $venue);

        $this->venue = $venue;
        $this->amenities = $venue->amenities ?? [];

        $stored = $venue->opening_hours ?? [];
        foreach (array_keys(config('courtgo.weekdays')) as $dow) {
            $day = $stored[$dow] ?? [];
            $this->openingHours[$dow] = [
                'closed' => (bool) ($day['closed'] ?? false),
                'open' => $day['open'] ?? '',
                'close' => $day['close'] ?? '',
            ];
        }

        $this->pricingNote = $venue->pricing_note ?? '';
        $this->announcement = $venue->announcement ?? '';
        $this->announcementActive = (bool) $venue->announcement_active;
        $this->announcementUntil = $venue->announcement_until?->toDateString();
        $this->policy = $venue->policy ?? '';
        $this->contactPhone = $venue->contact_phone ?? '';
        $this->contactWhatsapp = $venue->contact_whatsapp ?? '';
        $this->contactEmail = $venue->contact_email ?? '';
        $this->contactWebsite = $venue->contact_website ?? '';
        $this->contactInstagram = $venue->contact_instagram ?? '';
        $this->contactFacebook = $venue->contact_facebook ?? '';
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

    public function saveInfo(): void
    {
        $this->authorize('update', $this->venue);

        $this->validate([
            'openingHours.*.closed' => 'boolean',
            'openingHours.*.open' => 'nullable|date_format:H:i',
            'openingHours.*.close' => 'nullable|date_format:H:i',
            'pricingNote' => 'nullable|string|max:255',
            'announcement' => 'nullable|string|max:1000',
            'announcementActive' => 'boolean',
            'announcementUntil' => 'nullable|date|after_or_equal:today',
            'policy' => 'nullable|string|max:5000',
            'contactPhone' => 'nullable|string|max:50',
            'contactWhatsapp' => 'nullable|string|max:50',
            'contactEmail' => 'nullable|email|max:255',
            'contactWebsite' => 'nullable|url|max:255',
            'contactInstagram' => 'nullable|string|max:255',
            'contactFacebook' => 'nullable|string|max:255',
        ]);

        // An open day with both times set must close after it opens.
        foreach ($this->openingHours as $dow => $day) {
            if (empty($day['closed']) && $day['open'] !== '' && $day['close'] !== '' && $day['close'] <= $day['open']) {
                throw ValidationException::withMessages([
                    "openingHours.$dow.close" => 'Closing time must be after opening time.',
                ]);
            }
        }

        $this->venue->update([
            'opening_hours' => $this->openingHours,
            'pricing_note' => $this->pricingNote ?: null,
            'announcement' => $this->announcement ?: null,
            'announcement_active' => $this->announcementActive,
            'announcement_until' => $this->announcementUntil ?: null,
            'policy' => $this->policy ?: null,
            'contact_phone' => $this->contactPhone ?: null,
            'contact_whatsapp' => $this->contactWhatsapp ?: null,
            'contact_email' => $this->contactEmail ?: null,
            'contact_website' => $this->contactWebsite ?: null,
            'contact_instagram' => $this->contactInstagram ?: null,
            'contact_facebook' => $this->contactFacebook ?: null,
        ]);

        session()->flash('status', 'Venue details saved.');
    }

    public function render()
    {
        return view('livewire.owner.venues.profile', [
            'allAmenities' => config('courtgo.amenities'),
            'weekdays' => config('courtgo.weekdays'),
            'photos' => $this->venue->photos()->get(),
        ]);
    }
}
