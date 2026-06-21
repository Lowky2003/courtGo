<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AdminOnly;
use App\Models\SessionTemplate;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Venue details')]
class VenueShow extends Component
{
    use AdminOnly;

    public Venue $venue;

    /** Whether the reject-reason box is open, and its text. */
    public bool $rejecting = false;

    public string $rejectionReason = '';

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
    }

    /** Tick or untick a verification item (e.g. after checking the uploaded document). */
    public function toggleVerified(string $key): void
    {
        if (! in_array($key, Venue::verificationKeys(), true)) {
            return;
        }

        $items = $this->venue->verified_items ?? [];
        $alreadyVerified = in_array($key, $items, true);

        // Can't verify an item the owner hasn't uploaded a document for.
        if (! $alreadyVerified && ! $this->venue->documents()->where('type', $key)->exists()) {
            return;
        }

        $items = $alreadyVerified
            ? array_values(array_diff($items, [$key]))
            : [...$items, $key];

        $this->venue->update(['verified_items' => $items]);
    }

    /** Approve this venue so it becomes visible and bookable — only once fully verified. */
    public function approve(): void
    {
        $this->venue->refresh(); // guard against a stale page

        if ($this->venue->isApproved() || ! $this->venue->isFullyVerified()) {
            return; // already approved, or not every verification item is ticked
        }

        $this->venue->approveByAdmin();
        $this->rejecting = false;
    }

    /** Open / cancel the reject-reason box. */
    public function startReject(): void
    {
        $this->rejecting = true;
    }

    public function cancelReject(): void
    {
        $this->rejecting = false;
        $this->reset('rejectionReason');
        $this->resetValidation();
    }

    /** Reject the venue with a reason that's emailed to and shown to the owner. */
    public function reject(): void
    {
        // Trim BEFORE validating — Livewire skips the global TrimStrings middleware,
        // so otherwise a whitespace-only reason would pass min:5 and store blank.
        $this->rejectionReason = trim($this->rejectionReason);

        $this->validate(
            ['rejectionReason' => 'required|string|min:5|max:1000'],
            ['rejectionReason.required' => 'Please give the owner a reason for the rejection.'],
        );

        $this->venue->refresh();

        // A stale page: the venue was approved in the meantime — don't silently revoke it.
        if ($this->venue->isApproved()) {
            $this->rejecting = false;

            return;
        }

        $this->venue->rejectByAdmin($this->rejectionReason);

        $this->rejecting = false;
        $this->reset('rejectionReason');
    }

    public function render()
    {
        $this->venue->load(['owner', 'courts' => fn ($q) => $q->orderBy('name'), 'photos', 'documents']);

        // Price range across all active slots (not gated on bookable, so admins
        // can review pricing even before the venue is approved).
        $prices = SessionTemplate::query()
            ->where('is_active', true)
            ->whereHas('court', fn ($q) => $q->where('venue_id', $this->venue->id))
            ->pluck('price');

        return view('livewire.admin.venue-show', [
            'subscription' => $this->venue->owner->subscription($this->venue->subscriptionType()),
            'priceRange' => $prices->isEmpty() ? null : ['min' => (float) $prices->min(), 'max' => (float) $prices->max()],
            'verificationItems' => config('courtgo.verification'),
            'documents' => $this->venue->documents->groupBy('type'),
        ]);
    }
}
