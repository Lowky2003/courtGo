<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Manage Owners')]
class Owners extends Component
{
    public function toggleSuspend(int $userId): void
    {
        $owner = User::where('role', UserRole::Owner->value)->findOrFail($userId);

        $owner->update(['is_suspended' => ! $owner->is_suspended]);
    }

    /** Approve a pending owner so they can go live (once subscribed + Connect-onboarded). */
    public function approve(int $userId): void
    {
        $owner = User::where('role', UserRole::Owner->value)->findOrFail($userId);

        $owner->update(['approved_at' => now()]);
    }

    public function render()
    {
        return view('livewire.admin.owners', [
            'owners' => User::where('role', UserRole::Owner->value)
                ->withCount('venues')
                ->orderByRaw('approved_at IS NULL DESC') // pending owners first
                ->orderBy('name')
                ->get(),
        ]);
    }
}
