<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('courts.browse')" wire:navigate icon="arrow-left">Back to search</flux:button>
        <flux:heading size="xl">{{ $court->venue->name }}</flux:heading>
        <flux:text>{{ $court->name }} · {{ $court->sport }} · 📍 {{ $court->venue->city }}, {{ $court->venue->state }}</flux:text>
        @if ($court->venue->description)
            <flux:text class="text-zinc-500">{{ $court->venue->description }}</flux:text>
        @endif
    </div>

    @if (session('booking_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('booking_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Pick a date --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
        <flux:input type="date" wire:model.live="date" label="Choose a date" :min="now()->toDateString()" />

        <div>
            <flux:heading size="lg">Available sessions</flux:heading>
            @if ($sessions->isEmpty())
                <flux:text class="text-zinc-400">No sessions available on this date. Try another day.</flux:text>
            @else
                <div class="mt-3 space-y-2">
                    @foreach ($sessions as $session)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3" wire:key="slot-{{ $session->id }}">
                            <div>
                                <span class="font-medium">
                                    {{ \Illuminate\Support\Carbon::parse($session->start_time)->format('g:i A') }}
                                    – {{ \Illuminate\Support\Carbon::parse($session->end_time)->format('g:i A') }}
                                </span>
                                <span class="text-zinc-500 ml-2">RM {{ number_format($session->price, 2) }}</span>
                            </div>
                            <flux:button size="sm" variant="primary"
                                href="{{ route('bookings.checkout', ['court' => $court, 'session' => $session, 'date' => $date]) }}">
                                Book &amp; pay
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
