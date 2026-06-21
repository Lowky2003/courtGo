<?php

namespace App\Livewire\Concerns;

use App\Enums\UserRole;

/**
 * Enforce admin-only access on EVERY Livewire request (mount and actions).
 *
 * Route middleware (role:admin) only guards the initial page load, not the
 * Livewire /livewire/update action endpoint. Livewire calls bootAdminOnly()
 * on every request, so mutating actions (approve, reject, suspend, …) are
 * always re-authorized here.
 */
trait AdminOnly
{
    public function bootAdminOnly(): void
    {
        abort_unless(auth()->user()?->role === UserRole::Admin, 403);
    }
}
