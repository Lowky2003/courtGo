<?php

namespace App\Livewire\Owner\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('My Venues')]
class Index extends Component
{
    use AuthorizesRequests, WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|string|max:255')]
    public string $address = '';

    #[Validate('required|string|max:255')]
    public string $city = '';

    #[Validate('required|string|max:255')]
    public string $state = '';

    /** One optional photo of the place (shown to customers). */
    #[Validate('nullable|image|max:2048')]
    public $image;

    public function save(): void
    {
        $validated = $this->validate();

        // Keep state within the curated list (the dropdown enforces this in the UI).
        $this->validate(['state' => ['required', Rule::in(config('courtgo.states'))]]);

        $data = collect($validated)->except('image')->all();

        if ($this->image) {
            $data['image_path'] = $this->image->store('venues', 'public');
        }

        auth()->user()->venues()->create($data);

        $this->reset('name', 'description', 'address', 'city', 'state', 'image');
    }

    public function delete(int $venueId): void
    {
        $venue = Venue::findOrFail($venueId);

        $this->authorize('delete', $venue);

        $venue->delete();
    }

    public function render()
    {
        return view('livewire.owner.venues.index', [
            'venues' => auth()->user()->venues()->withCount('courts')->latest()->get(),
        ]);
    }
}
