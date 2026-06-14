<div class="space-y-8 p-6 max-w-5xl mx-auto w-full">
    <div class="space-y-1">
        <flux:heading size="xl">Find a Court</flux:heading>
        <flux:text>Search courts you can book right now.</flux:text>
    </div>

    {{-- Search --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="sport" label="Sport" placeholder="e.g. Badminton" />
        <flux:input wire:model.live.debounce.300ms="city" label="City" placeholder="e.g. Subang Jaya" />
    </div>

    {{-- Results --}}
    @if ($courts->isEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center">
            <flux:text>No bookable courts found. Try a different search — or an owner may still need to go live.</flux:text>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($courts as $court)
                <a href="{{ route('courts.show', $court) }}" wire:navigate wire:key="court-{{ $court->id }}"
                   class="block rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 hover:border-blue-400 transition">
                    <flux:badge size="sm" color="blue">{{ $court->sport }}</flux:badge>
                    <div class="mt-2 font-semibold">{{ $court->venue->name }}</div>
                    <div class="text-sm text-zinc-500">{{ $court->name }}</div>
                    <div class="text-sm text-zinc-500">📍 {{ $court->venue->city }}, {{ $court->venue->state }}</div>
                    <div class="mt-3">
                        <flux:button size="sm" variant="primary">View &amp; book</flux:button>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
