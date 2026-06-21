<div class="space-y-6 p-6 max-w-3xl mx-auto w-full">
    <flux:button size="sm" variant="ghost" :href="route('home')" wire:navigate icon="arrow-left">Back to homepage</flux:button>

    <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">My Bookings</flux:heading>

    {{-- Filters: status pills + a date filter --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            @foreach (['all' => 'All', 'confirmed' => 'Confirmed', 'awaiting' => 'Awaiting', 'cancelled' => 'Cancelled'] as $val => $label)
                <button type="button" wire:click="$set('filter', '{{ $val }}')"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $filter === $val ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <flux:input wire:model.live="date" type="date" icon="calendar-days" class="max-w-[11rem]" />
            @if ($date)
                <flux:button size="sm" variant="ghost" wire:click="clearDate">Clear</flux:button>
            @endif
        </div>
    </div>

    @if (session('booking_confirmed'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>Booking confirmed! See it below.</flux:callout.text>
        </flux:callout>
    @endif
    @if (session('booking_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('booking_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if (empty($todayGroups) && empty($otherGroups))
        <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
            <flux:icon name="ticket" class="size-8 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">No bookings here</flux:heading>
            <flux:text class="text-zinc-500">When you book a court it’ll show up here.</flux:text>
            <flux:button variant="primary" icon="magnifying-glass" :href="route('courts.browse')" wire:navigate>Find a court</flux:button>
        </div>
    @else
        @if (! empty($todayGroups))
            <div class="space-y-3">
                <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-zinc-500">
                    <span class="size-1.5 rounded-full bg-blue-500"></span> Booked today
                </h2>
                @foreach ($todayGroups as $group)
                    @include('partials.booking-group-card', ['group' => $group])
                @endforeach
            </div>
        @endif

        @if (! empty($otherGroups))
            <div class="space-y-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ empty($todayGroups) ? 'Bookings' : 'Booked earlier' }}</h2>
                @foreach ($otherGroups as $group)
                    @include('partials.booking-group-card', ['group' => $group])
                @endforeach
            </div>
        @endif
    @endif
</div>
